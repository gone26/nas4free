PORTNAME=			bash
PORTVERSION=		4.4
PORTREVISION?=	0
CATEGORIES=		shells
COMMENT=		GNU Project's Bourne Again SHell
MAINTAINER=		zoon1@nas4free.org


MASTER_SITES=		GNU/${PORTNAME}
DIST_SUBDIR=		${PORTNAME}
EXTRACT_ONLY=				${DISTNAME}${EXTRACT_SUFX}

OPTIONS_DEFINE=         IMPLICITCD COLONBREAKSWORDS 
OPTIONS_DEFAULT=        IMPLICITCD COLONBREAKSWORDS 
IMPLICITCD_DESC=        Use directory name alone to cd into it 
COLONBREAKSWORDS_DESC=  Colons break words 

COLONBREAKSWORDS_EXTRA_PATCHES= ${PATCHDIR}/extrapatch-colonbreakswords

GNU_CONFIGURE=  yes
USES=		  bison
USE_GETTEXT=	  yes
CONFIGURE_ARGS=	--without-bash-malloc \
								--disable-rpath \
								--enable-disabled-builtins \
								--with-libiconv-prefix=${ICONV_PREFIX} \
								--disable-help-builtin \
								--disable-static-link
CPPFLAGS+=			${PTHREAD_CFLAGS} \
								-I${LOCALBASE}/include
LDFLAGS+=				-L${LOCALBASE}/lib
CONFIGURE_ENV=	CPPFLAGS="${CPPFLAGS}" \
								LDFLAGS="${LDFLAGS}" \
								YACC="bison"

.include <bsd.port.pre.mk>

post-patch:
	@${REINPLACE_CMD} -e "s|%%PREFIX%%|${PREFIX}|g" ${WRKSRC}/doc/bash.1

post-configure:
	@${FIND} ${WRKSRC} -name Makefile -print0 | ${XARGS} -0 \
		${REINPLACE_CMD} -e "s|^DESTDIR *=|& ${DESTDIR}|"

pre-build:
	@${ECHO_CMD} $$((${PORTREVISION}-1)) > ${WRKSRC}/.build

do-install:
	@${INSTALL_PROGRAM} -v ${WRKSRC}/bash ${NAS4FREE_ROOTFS}/usr/local/bin
	@${LN} -fsv /usr/local/bin/bash ${NAS4FREE_ROOTFS}/usr/local/bin/rbash
	@${LN} -fsv /usr/local/bin/bash ${NAS4FREE_ROOTFS}/bin/bash

regress: build
	cd ${WRKSRC}; ${SETENV} ${MAKE_ENV} ${MAKE} ${MAKE_ARGS} test

ckp:
	${MAKE} -DPATCH_DEBUG clean patch

cklatest:
	@${ECHO} -n "Currently at: "
	@${MAKE} -V PATCHLEVEL
	-ncftpls \
	    ftp://ftp.cwru.edu/pub/${PORTNAME}/${PORTNAME}-${PORTVERSION:C/\.[0-9a-z]*$//}-patches/ \
		| fgrep -v .sig | ${TAIL}

.include <bsd.port.post.mk>
