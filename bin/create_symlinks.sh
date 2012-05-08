#!/bin/sh

basedir=`pwd`


coredir=$basedir/engine/pukiwiki-plus-i18n
if [ -e $coredir ]; then
	cd $coredir/skin
	if [ -e lightbox]; then
		rm lightbox
	fi
	ln -s -f ../../lightbox/ lightbox
	if [-e monobook]; then
		rm monobook
	fi
	ln -s -f ../../sonots_monobook_plus/skin/monobook/ monobook
else
	echo "[ERROR] the directory '$coredir' not found."
	exit -1
fi


imagedir=$coredir/image
if [ -e $imagedir ]; then
	cd $imagedir
	ln -s -f ../../sonots_monobook_plus/image/pukiwiki.plus_logo_trans.png pukiwiki.plus_logo_trans.png
else
	echo "[ERROR] the directory '$imagedir' not found."
	exit -1
fi


localedir=$coredir/extend/locale/ja_JP/LC_MESSAGES
if [ -e $localedir ]; then
	cd $localedir
	ln -s -f ../../../../../table_edit2/table_edit2.mo 
else
	echo "[ERROR] the directory '$localedir' not found."
	exit -1
fi


plugindir=$coredir/extend/plugin
if [ -e $plugindir ]; then
	cd $plugindir
	ln -s -f ../../../sonots_monobook_plus/plugin/monobook_navigation.inc.php monobook_navigation.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/monobook_recent.inc.php monobook_recent.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/style.inc.php style.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/lsx.inc.php lsx.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/monobook_toolbox.inc.php monobook_toolbox.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/wikinote.inc.php wikinote.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/monobook_search.inc.php monobook_search.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/monobook_login.inc.php monobook_login.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/monobook_getlink.inc.php monobook_getlink.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/tag.inc.php tag.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/revert.inc.php revert.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/monobook_popular.inc.php monobook_popular.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/relink.inc.php relink.inc.php
	ln -s -f ../../../sonots_monobook_plus/plugin/contentsx.inc.php contentsx.inc.php
	ln -s -f ../../../sonots_plus/delete.inc.php delete.inc.php
	ln -s -f ../../../sonots_plus/splitbody.inc.php splitbody.inc.php
	ln -s -f ../../../table_edit2/table_edit2.inc.php table_edit2.inc.php
else
	echo "[ERROR] the directory '$plugindir' not found."
	exit -1
fi

echo "created."
