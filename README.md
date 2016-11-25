PukiWiki Plus! Plus!(PPP)
=========================

**pukiwiki-plus-plus-i18n = [pukiwiki-plus-i18n][pukiwikiplus] + multiple sites supports + sonots plugins and skins([Monobook skin for PukiWiki Plus!][monobook])**


INTRODUCTION
------------
PukiWiki Plus! Plus!(PPP) makes it easy to duplicate the sites, contains some useful sonots plugins, and adopts the [Monobook skin for PukiWiki Plus!][monobook] : well-organized and easy to read.<br />

Including [PukiWiki Plus! for PPP][ppp] as GIT submodule, it is forked from [PukiWiki Plus!][pukiwikiplus].<br />
Thanks for their incredible jobs.


INSTALLATION
------------
1. Clone this project.

2. After `cd /path/to/PPP(contains README.md)`, do

        git submodule init
        git submodule update

    Now, [PukiWiki Plus!][pukiwikiplus] has cloned.

3. Next, do

        ./bin/create_symlinks.sh

    And you can access to sample site : http://your.web.server/ppp/sites/sample


HOW TO DUPLICATE A SITE
-----------------------
1. Copy an existing site directory recursively.

        cp -r sites/sample sites/new_wiki_name

1. After `cd /path/to/PPP(contains README.md)`, do

        ./bin/setup_site.sh new_wiki_name

    Now, you can access to new site : http://your.web.server/ppp/sites/new_wiki_name

2. edit files as you like:

        sites/new_wiki_name/index.php
        sites/new_wiki_name/data/*.usr.ini.php



LICENSE
-------
This project is no big deal because this is not too much to wrap some great projects or files.<br />
I want to provide this project as PUBLIC-DOMAIN (do not know how the laws of Japan treats it).

But some projects wrapped by this projects are displayed their license.<br />
Please follow them when you use this project not for personal use.


HELP!
-----
I am aware that my English is poor.<br />
Please send me your fixed README.md or fork and request to pull.



[pukiwikiplus]: https://github.com/miko2u/pukiwiki-plus-i18n
[ppp]:          https://github.com/yuki-takei/pukiwiki-plus-i18n-for-ppp
[monobook]:     http://lsx.sourceforge.jp/?Skin%2Fmonobook

