# Boilerplatypus

Now this is a very strange name. But it actually makes sense. Why? First, I only wanted to add H5BP to Stacey. But then Twitter Bootstrap appeared and I decided to play with it too. Bot how can I do that? With Git branches, my young padawan!

So, meet [the one, the only](http://www.youtube.com/watch?v=z8f2mW1GFSI), crossbreed of [Stacey](http://staceyapp.com/), [H5BP](http://h5bp.com) and [Bootstrap](http://twitter.github.com/bootstrap/): the Boilerplatypus!

# My Workflow (Mac only)

Instruments:

* GitHub
* MAMP http://www.mamp.info/en/index.html
* SASS http://sass-lang.com/

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

## SASS

You don't need to use it, you can just edit style.css file. But if you want to harness power of Syntactically Awesome Stylesheets, bend over: here's how you can install it:

	$ gem install sass

Then you need to run this commands and SASS will automatically convert all your .scss files within the public/docs/scss folder to .css files and put it to public/docs/css folder. Then, whenever you will change any .scss file, it will automatically rewrite corresponding .css file.

	$ cd ~/Hello-World
	$ scss --watch public/docs/scss:public/docs/css -t compressed

Note that the command is not "sass" but "scss", because I prefer to use SCSS syntax. Omit "-t compressed" if you want result css file to be uncompressed. Use "-t compressed" to produce CSS for live version.