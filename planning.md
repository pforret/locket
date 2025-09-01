# Project Planning

## Current Task

This is "Locket" - a Laravel alternative to Pocket (getpocket.com) for saving, organizing, and summarizing web links. It's a single-user application built with Laravel 12 and Vue 3 using Inertia.js.

## SHORT-TERM PLAN (Next 1-2 weeks)

### Input

- [ ] Build web GUI to add a new URL and optional title + tags
      Only the URL is asked first, then the app fetches title in the background. The title is then added as a second field that can be edited, along with tags.
- [ ] Build command line artisan command to add a new URL 
      Only the URL is asked first, then the app fetches title in the background. The title is then added as a second field that can be edited, along with tags
- [ ] Run the content extraction, summary extraction, image extraction, screenshot creator as separate queued jobs

### Output

- [ ] Build web GUI to list saved URLs, filter by tags, search by title/content
- [ ] Build detail view to show title, image, tags, date added, link to original URL, summary, full text


## LONG-TERM PLAN (Next 1-2 months)

### Input

- [ ] Build API endpoint to add a new URL and optional title + tags
- [ ] Build bookmarklet to add current page URL to the app
- [ ] Build email integration to add URLs by sending an email to a special address
- [ ] Build RSS feed integration to periodically fetch new URLs from a feed
- [ ] Build Zapier integration to add new URLs via Zapier
- [ ] Build iPhone share extension to add current page URL to the app
- [ ] Allow optional AI-generated summary and keywords using OpenAI

### Output

- [ ] Build RSS feed of saved URLs with optional tag filtering

## Notes

Develop step by step, plan first, then code, then test, then document.
