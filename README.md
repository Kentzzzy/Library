
# Library Management System API

This is a comprehensive and secure API built for managing library systems, developed using the Slim Framework and JWT authentication. The API allows for seamless management of users, books, and authors, ensuring efficient operations and security.


## Features
- **User Management**: Register, authenticate, and manage users with secure JWT tokens. 
- **Book Management**: Add, update, and delete books in the library catalog. 
- **Author Management**: Link books to authors and manage the library’s authorship. 
- **Security**: Data protection using JWT for authentication and BCRYPT for password hashing. 
- **Scalable Architecture**: Designed for small to large-scale libraries, supporting multiple users and books.

## Technologies Used
- **PHP (Slim Framework)**: A lightweight framework for building fast and secure APIs.
- **MySQL**: A relational database management system for storing data. 
- **JWT**: JSON Web Tokens for secure authentication. 
- **BCRYPT**: Password hashing algorithm for securing sensitive data.
## API Endpoints

#### USER Endpoints


  **Endpoint 1**: User Registration


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `POST` | `http://127.0.0.1/library/public/user/register` | Register a new user |

**Body** (JSON):

```http
{
  "username":"your_username",
  "password":"your_password"
}
```
 
  **Endpoint 2**: User Authentication


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `POST` | `http://127.0.0.1/library/public/user/auth` | Authenticate a user  |

**Body** (JSON):

```http
{
  "username":"your_username",
  "password":"your_password"
}
```
  **Endpoint 3**: Show All Users


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `GET` | `http://127.0.0.1/library/public/users` | Shows a list of all users  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
  **Endpoint 4**: Show User


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `GET` | `http://127.0.0.1/library/public/user/{userid}` | Show specific user by userid  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
  **Endpoint 5**: Update User


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `PUT` | `http://127.0.0.1/library/public/user/{userid}` | Update a specific user  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
**Body** (JSON):

```http
{
  "username": "your_new_username",
  "password": "your_new_password"
}
```
  **Endpoint 6**: Delete User


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `DELETE` | `http://127.0.0.1/library/public/user/{userid}` | Delete a specific user  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```

#### AUTHOR Endpoints

  **Endpoint 7**: Add Author


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `POST` | `http://127.0.0.1/library/public/author` | Add a new author  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
**Body** (JSON):

```http
{
  "name":"author_name"
}
```
  **Endpoint 8**: Show Author


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `GET` | `http://127.0.0.1/library/public/author/{auhtorid}` | Show specific author by authorid  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```

  **Endpoint 9**: Show Books by Author


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `GET` | `http://127.0.0.1/library/public/author/{auhtorid}/books` | Show all books by a specific author  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```

  **Endpoint 10**: Update Author


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `PUT` | `http://127.0.0.1/library/public/author/{auhtorid}` | Update a specific author  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
**Body** (JSON):
}
```http
{
  "name":"new_author_name"
}
```
  **Endpoint 11**: Delete Author


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `DELETE` | `http://127.0.0.1/library/public/author/{auhtorid}` | Delete a specific author  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```

#### BOOK Endpoints

  **Endpoint 12**: Add Book


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `POST` | `http://127.0.0.1/library/public/book` | Add a new book  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
**Body** (JSON):
}
```http
{
  "title":"book_title",
  "authorid":"author_id"
}
```
  **Endpoint 13**: Show Book


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `GET` | `http://127.0.0.1/library/public/book/{bookid}` | Get a specific book by bookid  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
  **Endpoint 14**: Update Book


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `PUT` | `http://127.0.0.1/library/public/book/{bookid}` | Update a specific book  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
**Body** (JSON):
}
```http
{
  "bookid": "book_id",
  "title": "new_book_title",
  "authorid": "author_id"
}
```
**Endpoint 15**: Delete Book


| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `DELETE` | `http://127.0.0.1/library/public/book/{bookid}` | Delete a specific book by bookid  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```

#### BOOK-AUTHOR Endpoints

**Endpoint 16**: Add Book-Author

| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `POST` | `http://127.0.0.1/library/public/books-authors` | Create new book-author collection  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
**Body** (JSON):
}
```http
{
  "bookid": "book_id",
  "authorid": "author_id"
}
```

**Endpoint 17**: Show Book-Author

| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `GET` | `http://127.0.0.1/library/public/books-authors/{collectionid}` | Show a specific book-author collection  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```

**Endpoint 18**: Update Book-Author

| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `PUT` | `http://127.0.0.1/library/public/books-authors/{collectionid}` | Update a specific book-author collection  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```
**Body** (JSON):
}
```http
{
  "collectionid": "your_new_collectionid",
  "bookid": "your_new_bookid",
  "authorid": "your_new_authorid"
}
```

**Endpoint 19**: Delete Book-Author

| Method | URL     | Description                |
| :-------- | :------- | :------------------------- |
| `DELETE` | `http://127.0.0.1/library/public/books-authors/{collectionid}` | Delete a specific book-author collection  |

**Header**:

```http
Authorization: Bearer <your_new_generated_token>
```

 
## Authored by:

#### Kenneth V. Almodovar/[@Kentzzzy](https://www.github.com/Kentzzzy)


