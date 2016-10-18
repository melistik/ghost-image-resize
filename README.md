# Ghost dynamic image resizing

![](ghost-image-resize.png)

The following little tool help's you to solve the missing feature in [ghost](https://github.com/TryGhost/Ghost) of image resizing. I guess they have it on their roadmap but until it's implemented this little tool helps.

## How it works

Mainly your blog will distribute the images from it's content-folder. So "content/images/..." will get accessed. By a little Mod-Rewrite it hooks in this request and redirects to a script that handels the image request for **jpg, png, gif**. It have implemented some options in order to control the distributed size of the images. By Parameter's you can control the hight, width and quality. Furthermore you can add a paramter in order to disable the hook to distribute the original images.

Example of the configures mod-rewrite for Apache

```
RewriteEngine On
RewriteCond %{HTTP_HOST} ^blog\.mpriess\.de$ [NC]
RewriteCond %{REQUEST_URI} ^/content/images/(.*)\.(jpg|jpeg|png|gif)$
RewriteCond %{QUERY_STRING} !^ignore$
RewriteRule ^/?(.*) http://im-cache.mpriess.de/im-cache.php/$1 [R=307,L]

```

Example of the configuration for Nginx
```
location ~ /content/images/(.+)\.(png|jpg|jpeg|gif)$ {
  if ($args = '') {
    rewrite ^/content/(.*)$ /_content/$1 last;
  }

  fastcgi_pass unix:/run/php/php7.0-fpm.sock;
  fastcgi_param PATH_INFO $1.$2;
  fastcgi_param SCRIPT_FILENAME [absolute path to im-cache]/im-cache.php;
  include fastcgi_params;
}

location /_content {
  internal;
  alias [absolute path to ghost root]/content/;
}
```

The list of possible paramerts:

* **h** for the height 
  * when set it resizes the image to best fit to the given height
* **w** for the width
  * same then height
* **q** for the quality
  * value between 0-100

## How to integrate it in your theme

Mainy you have the possibility to set default values for all parameters in the php-script (im-cache.php). This allows you to control the default image resizing when nothing is set. The are propably the normal image outputs within  the single post site. 

If you use blog-post-images these can be get accesed by your template scripts and in this case you can control it for example:


```
{{#foreach posts}}
<!-- start single post -->
<section class="col-sm-6 single-post">
	<article id="{{id}}" class="{{post_class}} {{#if image}}post-type-image{{/if}}">
		{{#if image}}
        <div class="featured media"><a href="{{url}}"><img src="{{image}}?w=555" alt="Post-image"></a></div>
		{{/if}}
		<div class="post-content">
			...
		</div>
	</article>
</section>
<!-- end single post -->
{{/foreach}}
```

As you can see in the post-loop for the overview you can extend the image url with **?w=555** your can further more you the **?ignoe** paramter for lightbox images or so...

Badfully currently it's not possible to replace the image url's within the post's content but therfore you have to set the default parameter's in the script to best fit to your theme.


## setup

Add and modify the mod-rewrite rule that is described above. Afterwards you need to configre the **im-cache.php** script.

* configure the path to the ghost's content-path
* fallback image size and quality
* add a colder cache with write rights for php

## thanks

to calviska that has written a very nice to use [SimpleImage](https://github.com/claviska/SimpleImage) script.

