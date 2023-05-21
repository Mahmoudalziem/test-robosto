#!/bin/bash

git stash && git pull && composer install

php artisan config:cache --env=testing
php artisan migrate:fresh
php artisan db:seed --class=TestDatabaseSeeder
php artisan test
php artisan config:cache