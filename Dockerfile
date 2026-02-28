FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy custom Apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Copy project files into Apache document root
COPY . /var/www/html/

# Set proper permissions for the data directory
RUN chown -R www-data:www-data /var/www/html/data && \
    chmod -R 755 /var/www/html/data

# Expose port 80 (Render maps this automatically)
EXPOSE 80
