# Updated 2022

This fork has been updated from the original version and enhanced with new features, including:
- Replaced [indivio.us](http://indivio.us) with [yewtu.be](http://yewtu.be) as the default instance, because [invidio.us](http://invidio.us) is defunct
- Added embeds for invidious videos when the feed is directly from an invidious instance, rather than youtube
- Added a "Watch on Youtube" link to allow watching videos on YouTube in cases where invidious doesn't work properly or is slow (optional, enable in settings)
- Base URL is no longer required in settings. You can now use a full invidious instance URL
- Major rewrite and simplification of codebase

The original readme is preserved below, with only minor changes.

---

# FreshRSS - Invidious videos extension

This FreshRSS extension allows you to directly watch YouTube videos displayed by [your favorite Invidious instance](https://github.com/omarroth/invidious) from within subscribed channel feeds.

To use it, upload the ```xExtension-Invidious``` directory to the FreshRSS `./extensions` directory on your server and enable it on the extension panel in FreshRSS.

This extension is originally forked from Kevin Papst's extension : [Freshrss-Youtube](https://github.com/kevinpapst/freshrss-youtube/).

## Installation

The first step is to put the extension into your FreshRSS extension directory:
```
cd /var/www/FreshRSS/extensions/
wget https://github.com/tunbridgep/freshrss-invidious/archive/master.zip
unzip master.zip
mv freshrss-invidious-master/xExtension-Invidious .
rm -rf freshrss-invidious-master/
```

Then switch to your browser https://localhost/FreshRSS/p/i/?c=extension and activate it.
You may want to configure it so you'd pick your favorite Invidious instance. [A list of existing instances is available on Github](https://github.com/omarroth/invidious/wiki/Invidious-Instances)

# Screenshots

With FreshRSS and an original Youtube Channel feed:
![screenshot before](https://cyphergoat.net/site/img/vrac/freshrss-sans-invidious.png?raw=true "Without this extension the video is not shown")

With activated Invidious extension :
![screenshot after](https://cyphergoat.net/site/img/vrac/freshrss-avec-invidious.png?raw=true "After activationg the extension you can enjoy your video directly in the FreshRSS stream")

Chose whether instance you'd like to use as your frontend :
![screenshot_params](https://cyphergoat.net/site/img/vrac/freshrss-invidious-parameters.png?raw=true "Configure height, width, and just write which domain to use for a personnalized experience")

## About FreshRSS
[FreshRSS](https://freshrss.org/) is a great self-hosted RSS Reader written in PHP, which is can also be found here at [GitHub](https://github.com/FreshRSS/FreshRSS).

More extensions can be found at [FreshRSS/Extensions](https://github.com/FreshRSS/Extensions).
