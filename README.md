# Movie API - Laravel GraphQL & Sanctum

A modern movie management API built with Laravel, featuring GraphQL integration via Lighthouse, Laravel Sanctum authentication, and robust database-driven movie search functionality.

## 🎬 Features

### Authentication (Laravel Sanctum)
- User registration and login
- Email verification
- Password reset with temporary password
- API token-based authentication
- Secure logout with token revocation

### Movie Management
- Database-first movie search
- Movie details viewing
- Local movie database management
- Advanced search with multiple criteria
- Pagination and sorting

### GraphQL API (Lighthouse)
- GraphQL endpoint at `/graphql`
- Advanced movie search with filters
- Type-safe queries and mutations
- Real-time data validation
- Optimized database queries

### Favorites System
- Add/remove movie favorites
- View personal favorites list
- Toggle favorite status
- Favorite statistics by genre

## 🛠 Technology Stack

- **Framework**: Laravel 11
- **GraphQL**: Lighthouse GraphQL 6.64.0
- **Authentication**: Laravel Sanctum
- **Database**: MySQL 8.0
- **ORM**: Eloquent
- **Containerization**: Docker with Docker Compose
- **Testing**: PHPUnit
- **API Documentation**: GraphQL Playground

## 🚀 Quick Setup (Docker Only)

> **Note:** All services (API, MySQL, nginx) run inside Docker containers. You do **not** need to run `php artisan serve` or install PHP/MySQL locally.

### 1. Clone the repository
```bash
git clone <repo-url>
cd movie-project/movie-api
```

### 2. Start all services
```bash
docker-compose up --build
docker-compose up -d
```
- API: http://localhost:8000
- GraphQL Playground: http://localhost:8000/graphql
- MySQL: localhost:3306 (see docker/mysql/my.cnf for config)

### 3. Run migrations & seeders (inside container)
```bash
php artisan migrate
php artisan movies:seed --count=100
```

### 4. Environment variables
- Edit `.env` for custom config if needed (default works for Docker setup)

### 5. Clear cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 6. Start server
```bash
php artisan serve
```

## 🔑 Password Reset Flow
- User requests password reset → receives a **temporary password** via email. (Currently, due to email issues, please check the password in the server logs.)
- Login with temporary password → **must change password** before using the app
- After password change, user can access all features

## 📝 Useful Commands
- Run tests: `docker-compose exec app php artisan test`
- Run migrations: `docker-compose exec app php artisan migrate`
- Seed database: `docker-compose exec app php artisan db:seed`
- Seed movies: `docker-compose exec app php artisan movies:seed --count=100`
- View logs: `docker-compose logs app`

## ⚠️ Troubleshooting
- Make sure Docker Desktop is running
- If port 8000/3306 is busy, change it in `docker-compose.yml`
- For database access, use credentials in `.env` and `docker/mysql/my.cnf`

## 📚 API Reference
- REST endpoints: `/api/*`
- GraphQL endpoint: `/graphql` (see `graphql/schema.graphql`)
- Auth endpoints: `/api/login`, `/api/register`, `/api/logout`, `/api/password/forgot`

## 🧑‍💻 Development Notes
- All code changes auto-reload in Docker (see `docker-compose.yml` for volumes)
- No need to run `php artisan serve` or install PHP locally
- For frontend, see `../movie-app/README.md`

---

## 📈 Version History

### v1.0.0 (Current)
- ✅ Initial release with GraphQL integration
- ✅ Laravel Sanctum authentication
- ✅ Database-first movie search
- ✅ Docker containerization
- ✅ Comprehensive API documentation

export const movieService = {
  // Search movies
  searchMovies: async (title, page = 1) => {
    const response = await axiosInstance.get(`/movies/search?title=${title}&page=${page}`);
    return response.data;
  },

  // Save movie to database
  saveMovie: async (imdbId) => {
    const response = await axiosInstance.post('/movies', { imdb_id: imdbId });
    return response.data;
  },

  // Add to favorites
  toggleFavorite: async (movieId) => {
    const response = await axiosInstance.post('/favorites/toggle', { movie_id: movieId });
    return response.data;
  },

  // Get favorites list
  getFavorites: async () => {
    const response = await axiosInstance.get('/favorites');
    return response.data;
  },
};

## 🐳 Docker Setup

### docker-compose.yml is already included in the project
```bash
# Start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# View logs
docker-compose logs -f app
```


## 🔧 Environment Variables

Required environment variables:

| Variable | Description | Required |
|----------|-------------|----------|
| `OMDB_API_KEY` | API key from OMDb | Yes |
| `DB_HOST` | Database host | Yes |
| `DB_DATABASE` | Database name | Yes |
| `SANCTUM_STATEFUL_DOMAINS` | Domains for React | Yes |
| `MAIL_HOST` | SMTP host for email | No |

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter AuthTest

# Create a new test
php artisan make:test MovieControllerTest
```

## 🚀 Production Deployment

### 1. Optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 2. Environment Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

### 3. HTTPS & Security
- Setup SSL certificate
- Configure CORS for production domains
- Use strong passwords for database

## ❗ Troubleshooting

### CORS Issues
```env
# Add React domain to Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,yourdomain.com
```

### Token Authentication
```javascript
// Ensure Bearer token is sent
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

### Database Connection
```bash
# Check MySQL container
docker-compose ps
docker-compose logs mysql
```

## 📝 License

MIT License - see [LICENSE](LICENSE) for details.

---

**Happy Coding!** 🎉

If you have any issues, create an issue or contact the developer.
