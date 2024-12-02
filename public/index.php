<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';
$app = new \Slim\App;

function getConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
}

$jwtMiddleware = function ($request, $response, $next) {
    $key = 'server_hack';
    $token = $request->getHeader('Authorization');

    if (!$token) {
        return $response->withStatus(401)->withJson(['status' => 'fail', 'message' => 'Token not provided']);
    }

    try {
        $jwt = str_replace('Bearer ', '', $token[0]);
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM tokens WHERE token = :token");
        $stmt->execute(['token' => $jwt]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Token does not exist");
        }

        $userid = $decoded->data->userid;
        $iat = time();
        $exp = $iat + 3600;
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $exp,
            'data' => ['userid' => $userid]
        ];

        $newToken = JWT::encode($payload, $key, 'HS256');
        $stmt = $conn->prepare("REPLACE INTO tokens (userid, token, expires_at) VALUES (:userid, :token, :expires_at)");
        $stmt->execute([
            'userid' => $userid,
            'token' => $newToken,
            'expires_at' => date('Y-m-d H:i:s', $exp)
        ]);

        $request = $request->withAttribute('jwtData', $decoded);
        $request = $request->withAttribute('newToken', $newToken);

        $response = $next($request, $response);
        return $response->withHeader('Authorization', 'Bearer ' . $newToken);

    } catch (Exception $e) {
        return $response->withStatus(401)->withJson(['status' => 'fail', 'message' => 'Invalid token: ' . $e->getMessage()]);
    }
};

$app->post('/user/register', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $uname = $data->username;
    $pass = $data->password;

    try {
        $conn = getConnection();
        $sql = "INSERT INTO users (username, password) VALUES(:uname, :pass)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'uname' => $uname,
            'pass' => password_hash($pass, PASSWORD_BCRYPT)
        ]);
        $response->getBody()->write(json_encode(["status" => "success", "data" => null]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/user/auth', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $uname = $data->username;
    $pass = $data->password;

    try {
        $conn = getConnection();
        $sql = "SELECT * FROM users WHERE username=:uname";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['uname' => $uname]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data && password_verify($pass, $data['password'])) {
            $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600,
                'data' => ['userid' => $data['userid']]
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');

            $stmt = $conn->prepare("INSERT INTO tokens (userid, token, expires_at) VALUES (:userid, :token, :expires_at)");
            $stmt->execute([
                'userid' => $data['userid'],
                'token' => $jwt,
                'expires_at' => date('Y-m-d H:i:s', $iat + 3600)
            ]);

            $response->getBody()->write(json_encode([
                "status" => "success",
                "token" => $jwt,
                "data" => null
            ]));
        } else {
            $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Authentication Failed"]]));
        }
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/users', function (Request $request, Response $response, array $args) {
    try {
        $conn = getConnection();
        $sql = "SELECT * FROM users";
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => $data,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["status" => "fail", "message" => $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware); 

$app->get('/user/{userid}', function (Request $request, Response $response, array $args) {
    $userid = $args['userid'];

    try {
        $conn = getConnection();
        $sql = "SELECT * FROM users WHERE userid=:userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['userid' => $userid]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => $data,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["status" => "fail", "message" => $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware); 

$app->put('/user/{userid}', function (Request $request, Response $response, array $args) {
    $userid = $args['userid'];
    $data = json_decode($request->getBody());
    $username = $data->username; 
    $password = isset($data->password) ? password_hash($data->password, PASSWORD_BCRYPT) : null;

    try {
        $conn = getConnection();
        $sql = "UPDATE users SET username=:username" . ($password ? ", password=:password" : "") . " WHERE userid=:userid";
        $stmt = $conn->prepare($sql);
        $params = [
            'username' => $username,
            'userid' => $userid
        ];
        if ($password) {
            $params['password'] = $password;
        }
        $stmt->execute($params);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "User updated successfully.",
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["status" => "fail", "message" => $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware); 

$app->delete('/user/{userid}', function (Request $request, Response $response, array $args) {
    $userid = $args['userid'];

    try {
        $conn = getConnection();
        $sql = "DELETE FROM users WHERE userid=:userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['userid' => $userid]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "User deleted successfully.",
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["status" => "fail", "message" => $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
})->add($jwtMiddleware); 

$app->post('/author', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $name = $data->name;

    try {
        $conn = getConnection();
        $sql = "INSERT INTO authors (name) VALUES(:name)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $name]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
})->add($jwtMiddleware); 

$app->get('/author/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    try {
        $conn = getConnection();
        $sql = "SELECT * FROM authors WHERE authorid=:authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['authorid' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => $data,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
})->add($jwtMiddleware); 

$app->get('/author/{id}/books', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    try {
        $conn = getConnection();
        $sql = "SELECT * FROM books WHERE authorid=:authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['authorid' => $id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => $data,
            "newToken" => $newToken  // Return new token
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
})->add($jwtMiddleware); 

$app->put('/author/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $data = json_decode($request->getBody());
    $name = $data->name;

    try {
        $conn = getConnection();
        $sql = "UPDATE authors SET name=:name WHERE authorid=:authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $name, 'authorid' => $id]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
})->add($jwtMiddleware); 

$app->delete('/author/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    try {
        $conn = getConnection();
        $sql = "DELETE FROM authors WHERE authorid=:authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['authorid' => $id]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
})->add($jwtMiddleware); 

$app->post('/book', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $title = $data->title;
    $authorid = $data->authorid;

    try {
        $conn = getConnection();
        $sql = "INSERT INTO books (title, authorid) VALUES(:title, :authorid)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['title' => $title, 'authorid' => $authorid]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "message" => $e->getMessage()
        ]));
    }
    return $response;
})->add($jwtMiddleware);

$app->get('/book/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    try {
        $conn = getConnection();
        $sql = "SELECT * FROM books WHERE bookid=:bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => $data,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "message" => $e->getMessage()
        ]));
    }
    return $response;
})->add($jwtMiddleware);

$app->put('/book/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $data = json_decode($request->getBody());
    $title = $data->title;
    $authorid = $data->authorid;

    try {
        $conn = getConnection();
        $sql = "UPDATE books SET title=:title, authorid=:authorid WHERE bookid=:bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['title' => $title, 'authorid' => $authorid, 'bookid' => $id]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "Book updated successfully.",
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "message" => $e->getMessage()
        ]));
    }
    return $response;
})->add($jwtMiddleware);


$app->delete('/book/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    try {
        $conn = getConnection();
        $sql = "DELETE FROM books WHERE bookid=:bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $id]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "Book deleted successfully.",
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "message" => $e->getMessage()
        ]));
    }
    return $response;
})->add($jwtMiddleware);

$app->post('/books_authors', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $authorid = $data->authorid;

    try {
        $conn = getConnection();
        $sql = "INSERT INTO books_authors (bookid, authorid) VALUES(:bookid, :authorid)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid, 'authorid' => $authorid]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
});

$app->get('/books_authors/{collectionid}', function (Request $request, Response $response, array $args) {
    $collectionid = $args['collectionid'];

    try {
        $conn = getConnection();
        $sql = "SELECT * FROM books_authors WHERE collectionid=:collectionid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['collectionid' => $collectionid]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => $data,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
});

$app->put('/books_authors/{collectionid}', function (Request $request, Response $response, array $args) {
    $collectionid = $args['collectionid'];
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $authorid = $data->authorid;

    try {
        $conn = getConnection();
        $sql = "UPDATE books_authors SET bookid=:bookid, authorid=:authorid WHERE collectionid=:collectionid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid, 'authorid' => $authorid, 'collectionid' => $collectionid]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null,
            "newToken" => $newToken  
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
});

$app->delete('/books_authors/{collectionid}', function (Request $request, Response $response, array $args) {
    $collectionid = $args['collectionid'];

    try {
        $conn = getConnection();
        $sql = "DELETE FROM books_authors WHERE collectionid=:collectionid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['collectionid' => $collectionid]);

        $newToken = $request->getAttribute('newToken');

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null,
            "newToken" => $newToken 
        ]));
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
    return $response;
});


$app->run();
?>

