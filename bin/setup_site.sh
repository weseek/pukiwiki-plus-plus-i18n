#!/bin/sh

basedir=`pwd`


if [ $# -ne 1 ]; then
	echo "usage: $0 WIKI_NAME"
	exit -1
fi

coredir=$basedir/engine/pukiwiki-plus-i18n
if [ ! -e $coredir ]; then
	echo "[ERROR] the directory '$coredir' not found."
	exit -1
fi


sitedir=$basedir/sites/$1
if [ -e $sitedir ]; then
	cd $sitedir
	ln -s -f ../../engine/pukiwiki-plus-i18n/image image
	ln -s -f ../../engine/pukiwiki-plus-i18n/skin skin
else
	echo "[ERROR] the directory '$sitedir' not found."
	exit -1
fi


datadir=$sitedir/data
if [ -e $datadir ]; then
	cd $datadir
	chmod 777 wiki diff backup cache counter
	chmod 666 wiki/* diff/* backup/* cache/* counter/*
else
	echo "[ERROR] the directory '$datadir' not found."
	exit -1
fi


echo "created."
