
This is a content management system based on Symfony.

## Features
The core model of this system is called node, every content is a node, such as news, notice, file(inlude image). There are many meta data for every node, such as author, created date time, current status etc. But the most important meta is dir, that means every node has a directory name, yes that just like the OS has. In other word all the nodes are organized with a folder system, we can access every node by click different folder name until to find what you want.


## Setup

### Setup Symfony
Because this system is based on Symfony, so you should setup symfony first. You can visit [Symfony setup link](https://symfony.com/doc/current/setup.html) for full document.

There is the composer way to setup symfony:
```
composer create-project symfony/website-skeleton my_project_name
```
or
```
composer create-project symfony/website-skeleton:^5.1 my_project_name
```

### Setup skating-bundle
1. Copy bundle files. Make dir heccjj under my_project_name folder, and then copy skating-bundle to this folder.
```
my_project_name
  |--heccjj
    |--skating-bundle
```

2. Update composer. Edit your project composer.json:
```
...
    "repositories": [
        {
            "type": "path",
            "url": "./heccjj/skating-bundle"
        }
    ]
...
```
And then add this line to you project composer.json:
```
"heccjj/skating-bundle": "*"
```
And then update composer:
```
composer update
```
3. Install assets:
```
bin/console ckeditor:install
bin/console assets:install public
```
4. Config yaml files:
    1.  Config route. Edit project route config file( config/route.yaml):

    ```
    node:
        resource: '../heccjj/skating-bundle/src/Resources/config/routes.yaml'
        prefix: /cms

    node_front:
        resource: '../heccjj/skating-bundle/src/Controller/FrontController.php'
        type:      annotation
    ```
    2. Config twig. Editor project twig file(config/packages/twig.yaml): 
    ```
    form_themes: ['bootstrap_4_horizontal_layout.html.twig']
    ```
    3. Config ckeditor. Editor project ckeditor file( config/packages/fos_ckeditor.yaml):
    ```
    fos_ck_editor:
        configs:
            skating_config:
                toolbar:    [ [ 'Source'] ,[ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ],[ 'Find','Replace','-','SelectAll','-','Scayt' ],['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar' ], [ 'Styles','Format' ],[ 'Bold','Italic','Strike','-','RemoveFormat' ], [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote' ], [ 'Link','Unlink','Anchor' ],[ 'Maximize','-','About' ] ]
    ```

    4. Config beelab. Edit file config/packages/beelab_tag.yaml:
    ```
    beelab_tag:
        tag_class: Heccjj\SkatingBundle\Entity\Tag
        purge: false
    ```

5. Config Database:
    1. Config env. Edit project env file( .env ):

    ```
    DATABASE_URL=mysql://root:your_mysql_root_password@db:3306/symfony?serverVersion=5.7
    ```

    2. Migrate:
    ```
    bin\console make:migration
    bin\console doctrine:migrations:migrate
    ```

You can visit http://localhost/node/ to view now.

6. By default, we use [xunsearch](http://www.xunsearch.com/) as the full text search, so you should setup and config xunsearch. If you dont want to use full text search, you can edit src\Controller\NodeController.php :
```
use Heccjj\SkatingBundle\Lib\Search\SearchXS as Search;
``` 
to 
```
use Heccjj\SkatingBundle\Lib\Search\SearchNull as Search;
```