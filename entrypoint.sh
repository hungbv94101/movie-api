#!/bin/bash
set -e

echo "🚀 Container started. Ready for manual commands."

# echo "🚀 Starting Laravel Movie API entrypoint..."

# # Function to wait for database
# wait_for_db() {
#     echo "⏳ Waiting for MySQL connection..."
#     echo "   Host: ${DB_HOST:-db}"
#     echo "   Database: ${DB_DATABASE:-movie_db}"
#     echo "   Username: ${DB_USERNAME:-laravel}"
#     echo "   Password: ${DB_PASSWORD:-password}"
#     # Wait for MySQL to be ready and grant privileges if needed
#     until mysql --skip-ssl -h"${DB_HOST:-db}" -u"${DB_USERNAME:-laravel}" -p"${DB_PASSWORD:-password}" -e "USE ${DB_DATABASE:-movie_db};" > /dev/null 2>&1; do
#         echo "MySQL is unavailable - sleeping"
#         # Try to grant privileges for laravel user using root
#         mysql --skip-ssl -h"${DB_HOST:-db}" -u"${DB_ROOT_USERNAME:-root}" -p"${DB_ROOT_PASSWORD:-root}" -e "GRANT ALL PRIVILEGES ON ${DB_DATABASE:-movie_db}.* TO 'laravel'@'%'; FLUSH PRIVILEGES;" || true
#         sleep 3
#     done
#     echo "✅ MySQL is ready!"
# }

# # Function to run migrations
# run_migrations() {
#     echo "🗃️ Running database migrations..."
#     php artisan migrate --force
    
#     if [ $? -eq 0 ]; then
#         echo "✅ Migrations completed successfully"
#     else
#         echo "❌ Migration failed"
#         exit 1
#     fi
# }

# # Function to seed master data
# seed_master_data() {
#     echo "🌱 Master data seeding skipped (manual seeding required)"
#     echo "📊 Current database status:"
    
#     # Get counts safely
#     USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
#     MOVIE_COUNT=$(php artisan tinker --execute="echo \App\Models\Movie::count();" 2>/dev/null | tail -1)
#     echo "   Users: ${USER_COUNT:-0}"
#     echo "   Movies: ${MOVIE_COUNT:-0}"
#     echo ""
#     echo "💡 To seed data manually:"
#     echo "   docker-compose exec app php artisan db:seed"
#     echo "   docker-compose exec app php artisan movies:seed --limit=50"
# }

# # Function to clear caches
# clear_caches() {
#     echo "🧹 Clearing application caches..."
#     php artisan config:clear
#     php artisan cache:clear
#     php artisan route:clear
#     php artisan view:clear
#     echo "✅ Caches cleared"
# }

# # Function to optimize application
# optimize_app() {
#     echo "⚡ Optimizing application..."
#     php artisan config:cache
#     php artisan route:cache
#     php artisan view:cache
#     echo "✅ Application optimized"
# }

# # Function to set permissions
set_permissions() {
    echo "🔐 Setting proper permissions..."
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache
    echo "✅ Permissions set"
}

# # Main execution
main() {
#     echo "🏁 Starting setup process..."
    
#     # Wait for database to be ready
#     wait_for_db
    
#     # Run migrations
#     run_migrations
    
#     # Seed master data
#     seed_master_data
    
#     # Clear caches
#     clear_caches
    
#     # Set permissions
    set_permissions
    
#     # Optimize for production if not in debug mode
#     if [ "${APP_DEBUG}" != "true" ]; then
#         optimize_app
#     fi
    
#     echo "🎉 Setup completed successfully!"
#     echo "📋 Application Status:"
#     echo "   Environment: ${APP_ENV:-production}"
#     echo "   Debug Mode: ${APP_DEBUG:-false}"
#     echo "   Database: Connected"
    
#     # Get counts safely
#     USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
#     MOVIE_COUNT=$(php artisan tinker --execute="echo \App\Models\Movie::count();" 2>/dev/null | tail -1)
#     echo "   Users: ${USER_COUNT:-0}"
#     echo "   Movies: ${MOVIE_COUNT:-0}"
    
#     # Execute the main container command
#     exec "$@"
}

# # Run main function
main "$@"