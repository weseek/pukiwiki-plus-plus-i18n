PukiWiki Plus! Plus!(PPP)
=========================

**pukiwiki-plus-plus-i18n = [pukiwiki-plus-i18n][pukiwikiplus] + multiple sites supports + sonots plugins and skins([Monobook skin for PukiWiki Plus!][monobook])**


INTRODUCTION
------------
PukiWiki Plus! Plus!(PPP) is wrapping [PukiWiki Plus!][pukiwikiplus](as GIT submodule).<br />
It makes it easy to duplicate the sites, contains some useful sonots plugins, and adopts the [Monobook skin for PukiWiki Plus!][monobook] : well-organized and easy to read.


INSTALLATION
------------
After `cd /path/to/PPP(contains README.md)`, do

    ./bin/create_symlinks.sh

And you can access to sample site : http://your.web.server/ppp/sites/sample


HOW TO DUPLICATE A SITE
-----------------------
1. After `cd /path/to/PPP(contains README.md)`, do

    <code>./bin/duplicate.sh sample new_wiki_name</code>

    Now, you can access to new site : http://your.web.server/ppp/sites/new_wiki_name

2. edit files as you like:

    <code>sites/new_wiki_name/index.php
sites/new_wiki_name/data/*.usr.ini.php</code>



LICENSE
-------
This project is no big deal though wrapping some incredible projects or files.<br />
I want to provide this project as PUBLIC-DOMAIN (do not know how the laws of Japan treats it).

But some projects wrapped by this projects are displayed their license.<br />
Please follow them when you use this project not for personal use.


HELP!
-----
I am aware that my English is poor.<br />
Please send me your fixed README.md or fork and request to pull.



[pukiwikiplus]: https://github.com/miko2u/pukiwiki-plus-i18n
[monobook]:     http://lsx.sourceforge.jp/?Skin%2Fmonobook

