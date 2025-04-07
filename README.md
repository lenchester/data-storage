# File Management System

This project is a File Management System I built with Symfony. It allows users to upload, manage, and download files securely.

### Why EasyAdmin bundle?
https://symfony.com/bundles/EasyAdminBundle/current/index.html

This task was very time-limited for me because I’m also working full-time. So I decided to use EasyAdmin to generate the CRUD functionality quickly. I’m actually able to build all of that in pure Symfony as well, but I felt I wouldn’t have enough time to do it from scratch.
## Service Layer Logic

My service layer, particularly the `FileService`, encapsulates the core business logic for file management. Here's a brief overview:

### FileService

The `FileService` class handles file-related operations.

Key functionalities include:

- **File Upload**: Handles the upload of files, generating unique names and storing them securely.
- **File Size Calculation**: Provides methods to get file sizes in bytes and convert them to human-readable formats.
- **File Download**: Creates streamed responses for secure file downloads.
- **File Management**: Includes methods for checking file existence, deleting files, and getting file extensions.

### FileEntityBuilder

The `FileEntityBuilder` class is responsible for constructing `File` entities. It encapsulates the logic for setting various properties of a `File` entity based on an uploaded file and user information.


## Getting Started
1. Copy .env into .env.local file
2. Run composer install with web container
   1. `docker exec -it web bash`
   2. `composer install`
3. From web container: `php bin/console doctrine:migrations:migrate`
4. From web container: `php bin/console user:create user pass`
5. http://localhost:8080/ Now you can login and start uploading files

