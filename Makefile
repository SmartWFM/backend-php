.PHONY: apidoc archive

ARCHIVE_VERSION=master
ARCHIVE_PREFIX=backend-php-${ARCHIVE_VERSION}
ARCHIVE_NAME=backend-php-${ARCHIVE_VERSION}
ARCHIVE_PATH=./dist/

apidoc:
	doxygen doc/Doxyfile

archive:
	mkdir -p ${ARCHIVE_PATH}
	git archive --prefix=${ARCHIVE_PREFIX}/ ${ARCHIVE_VERSION} -o ${ARCHIVE_PATH}${ARCHIVE_NAME}.tar.gz
	git archive --prefix=${ARCHIVE_PREFIX}/ ${ARCHIVE_VERSION} -o ${ARCHIVE_PATH}${ARCHIVE_NAME}.tar.bz2
	git archive --prefix=${ARCHIVE_PREFIX}/ ${ARCHIVE_VERSION} -o ${ARCHIVE_PATH}${ARCHIVE_NAME}.zip

clean:
	rm -rf doc/apidoc
	rm -rf ${ARCHIVE_PATH}

remove_whitespace:
	# remove whitespaces at the end of a line
	find ./src/commands ./src/lib/SmartWFM ./src/lib/search ./src/lib/archives -name *.php -exec sed -i 's/[ \t]*$$//' {} \;
