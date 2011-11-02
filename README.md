# Stacey 2.3.0 CMS with HTML5 (boilerplate) obfuscated template

This is not simple merging of Stacey + HTML5 Boilerplate, because I don't need all techniques from HTML5B, because I use this repo for myself on real projects.

## Stacey 2.3.0

https://github.com/kolber/stacey

## HTML5 Boilerplate

https://github.com/paulirish/html5-boilerplate

# My Workflow (Mac only)

Instruments:

* GitHub
* MAMP http://www.mamp.info/en/index.html
* LESS http://incident57.com/less/

## First, you need to create a repo on GitHub. http://help.github.com/create-a-repo/

	$ cd ~
	$ git clone git://github.com/Kvakes/stacey-html5.git
	$ mkdir ~/Hello-World
	$ cd ~/Hello-World
	$ git init
	$ cp -r ../stacey-html5/* .
	$ git add *
	$ git commit -m "stacey-html5"
	$ git remote add origin git@github.com:username/Hello-World.git
	$ git push origin master

## Download and install MAMP

Once you've started MAMP, click Preferences, select Apache tab and set Document Root to ~/Hello-World

## LESS App

Launch it, drag files from ~/Hello-World/public/docs/less folder to the app. Set output directory for those files to ~/Hello-World/public/docs/css

PROFIT!