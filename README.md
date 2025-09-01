# pforret/locket

A Laravel alternative to https://getpocket.com/. A single user application to save, organize, and summarize web links.

## Features

### Data structure

* The input is normally just a URL, with optionally a custom title and/or tags
* The app fetches the URL and extracts metadata (title, author, images, content, etc)
* The app also generates a preview image (using spatie/browsershot)
* The app also generated a Markdown version of the main content (using spatie/html-to-markdown)
* The app can optionally use AI to generate a summary and/or keywords
* The metadata is stored in a database table (configurable)
* The app also allows adding tags (using spatie/laravel-tags) 

### Input

How can new URLs be added?

* via web GUI (via Laravel Filament)
* via API (Laravel Sanctum authentication)
* via command line (artisan command)
* via bookmarklet
* via email (using a special email address)
* via RSS feed (periodically fetch new URLs from a feed)
* via Zapier integration
* iPhone share extension


### Output

#### Web GUI

Homepage: list view

* List of saved URLs, reverse chronological, with pagination
* Filter by tags
* Search by title/content
* View details of a saved URL (title, summary, images)

Detail view

* Title and principal image or generated screenshot
* list of tags
* date added
* link to original URL
* summary (if available)
* full text without ads

* also works well on mobile

#### API

* REST API to list, add, update, delete saved URLs
* Filter by tags, search by title/content
* API authentication via Laravel Sanctum

#### RSS

* RSS feed of saved URLs (configurable) on /feed/rss.xml
* Filter by tags via feed/{tag}/rss.xml

