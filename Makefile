.PHONY: apidoc archive

BIN_PATH=/usr/bin/
ARCHIVE_VERSION=HEAD
ARCHIVE_PREFIX=SmartWFM-backend-php-${ARCHIVE_VERSION}
ARCHIVE_NAME=SmartWFM-backend-php-${ARCHIVE_VERSION}
ARCHIVE_PATH=./dist/

apidoc:
	doxygen doc/Doxyfile

archive:
	mkdir -p ${ARCHIVE_PATH}
	git archive --format=tar --prefix=${ARCHIVE_PREFIX}/ ${ARCHIVE_VERSION} | gzip -9 > ${ARCHIVE_PATH}${ARCHIVE_NAME}.tar.gz
	git archive --format=tar --prefix=${ARCHIVE_PREFIX}/ ${ARCHIVE_VERSION} | bzip2 -9 > ${ARCHIVE_PATH}${ARCHIVE_NAME}.tar.bz2
	git archive --format=zip --prefix=${ARCHIVE_PREFIX}/ ${ARCHIVE_VERSION}  > ${ARCHIVE_PATH}${ARCHIVE_NAME}.zip

clean:
	rm -rf doc/apidoc
	rm -rf ${ARCHIVE_PATH}

remove_whitespace:
	# remove whitespaces at the end of a line
	find ./src/commands ./src/lib/SmartWFM ./src/lib/search ./src/lib/archives -name *.php -exec sed -i 's/[ \t]*$$//' {} \;
