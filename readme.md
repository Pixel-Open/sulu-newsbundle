# Sulu news bundle

![GitHub release (with filter)](https://img.shields.io/github/v/release/Pixel-Open/sulu-newsbundle?style=for-the-badge)
[![Dependency](https://img.shields.io/badge/sulu-2.5-cca000.svg?style=for-the-badge)](https://sulu.io/)

## Presentation
A Sulu bundle to manage the news of your website.

## Requirements

* PHP >= 8.0
* Sulu >= 2.4.*
* Symfony >= 5.4
* Composer

## Features
* List view of News (smart content)
* Without elasticsearch
* Routing
* Preview
* SULU Media include
* Content Blocks (Title,Editor,Image,Quote)
* Activity Log
* Trash
* Automation
* SEO

## Installation

### Install the bundle

Execute the following [composer](https://getcomposer.org/) command to add the bundle to the dependencies of your
project:

```bash
composer require pixelopen/sulu-newsbundle --with-all-dependencies
```

### Enable the bundle

Enable the bundle by adding it to the list of registered bundles in the `config/bundles.php` file of your project:

 ```php
 return [
     /* ... */
     Pixel\NewsBundle\NewsBundle::class => ['all' => true],
 ];
 ```

### Update schema
```shell script
bin/console do:sch:up --force
```

## Bundle Config

Define the Admin Api Route in `routes_admin.yaml`
```yaml
news.news_api:
  type: rest
  prefix: /admin/api
  resource: pixel_news.news_route_controller
  name_prefix: news.
```

## Use
### Add/Edit a news
Go to the "News" section in the administration interface. Then, click on "Add".
Fill the fields that are needed for your use.

Here is the list of the fields:
* Title (mandatory)
* URL (mandatory and filled automatically according to the title)
* Published at (manually filled)
* Cover
* Category (mandatory)
* Content

The "Content" field is a block content type. The different type of block come from the [Sulu Block Bundle](https://github.com/Pixel-Developpement/sulu-block-bundle)

Once you finished, click on "Save"

Your news is not visible on the website yet. In order to do that, click on "Publish?". It should be now visible for visitors.

To edit a news, simply click on the pencil at the left of the news you wish to edit.

### Categories
As you may have seen in the previous section, a news needs a category. These categories need to be created in a very specific way:
* You **must** create a root category which **must** have its key named "news"
* Then, under this root category, you create all the news categories you need

### Remove/Restore a gallery

There are two ways to remove a news:
* Check every news you want to remove and then click on "Delete"
* Go to the detail of a news (see the "Add/Edit a news" section) and click on "Delete".

In both cases, the news will be put in the trash.

To access the trash, go to the "Settings" and click on "Trash".
To restore a news, click on the clock at the left. Confirm the restore. You will be redirected to the detail of the news you restored.

To remove permanently a news, check all the news you want to remove and click on "Delete".

## Settings

This bundle comes with settings. There is only one setting, it's the configuration of a default image.
To access the bundle settings, go to "Settings > News management".

## Twig extension
There are several twig functions in order to help you use the news and settings on your website:

**get_latest_news(limit, local)**: returns the latest news. It takes two parameters:
* limit: represents the number of the latest news to display. If no limit is provided, the default value is 3
* locale: the locale in which the news should be retrieved. If no local is provided, the default value is 'fr'

Example of use:
```twig
<div class="w-full flex flex-row gap-6 justify-between">
    {% set news = get_latest_news(4, app.request.locale) %}
    {% for new in news %}
        <div class="containerAvis bg-white rounded-xl p-6 w-1/4">
            <a href="{{ sulu_content_path(new.routePath) }}" class="no-underline">
                {% if new.cover.id is defined %}
                    {% set cover = sulu_resolve_media(new.cover.id, 'fr') %}
                    <img src="{{ cover.thumbnails["991x"] }}" alt="{{ new.title }}"
                         class="w-full h-40 object-cover object-center bg-black mt-0 mb-4">
                {% endif %}
                <h3 class="block text-center text-base font-bold">{{ new.title }}</h3>
                <p class="block text-center">{{ new.publishedAt|date("d/m/Y") }} {#- {{ new.category }} #}</p>
            </a>
        </div>
    {% endfor %}
</div>
```

**get_latest_news_html(limit, locale)**: same as get_latest_news but it renders a view instead. It takes two parameters:
* limit: represents the number of the latest news to display. If no limit is provided, the default value is 3
* locale: the locale in which the news should be retrieved. If no local is provided, the default value is 'fr'

Example of use:
```twig
<div>
    {{ get_latest_news_html(4, app.request.locale) }}
</div>
```

**news_settings()**: returns the settings of the bundle. No parameters are required.

Example of use:

```twig
{% set newsSettings = news_settings() %}
{% if news.cover is not empty %}
    {% set cover = sulu_resolve_media(news.cover.id, 'en' %}
    <img src="{{ cover.thumbnails['991x'] }}" alt="{{ news.name }}">
{% else %}
    {% set default = sulu_resolve_media(newsSettings.defaultImage.id, 'en' %}
    <img src="{{ default.thumbnails['991x'] }}" alt="Default news image">
{% endif %} 
```

## Contributing
You can contribute to this bundle. The only thing you must do is respect the coding standard we implement.
You can find them in the `ecs.php` file.
