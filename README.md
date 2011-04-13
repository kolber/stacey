# Stacey 2.3.0

## Overview
Stacey takes content from `.txt` files, image files and implied directory structure and generates a website.
It is a no-database, dynamic website generator.

If you look in the `/content` and `/templates` folders, you should get the general idea of how it all works.

## Installation

Copy to server, `chmod 777 app/_cache`.

If you want clean urls, `mv htaccess .htaccess`

## Templates

There are an additional two sets of templates which can be found at:
<http://github.com/kolber/stacey-template2> &
<http://github.com/kolber/stacey-template3>

## Read More

See <http://staceyapp.com> for more detailed usage information.

## Copyright/License

Copyright (c) 2009 Anthony Kolber. See `LICENSE` for details.
Except PHP Markdown Extra which is (c) Michel Fortin (see `/app/parsers/markdown-parser.inc.php` for details) and
JSON.minify which is (c) Kyle Simpson (see 'app/parsers/json-minifier.inc.php' for details).