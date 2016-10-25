--- common/md5.c.orig	2014-10-21 17:57:11.000000000 +0200
+++ common/md5.c	2016-09-23 13:12:13.000000000 +0200
@@ -46,7 +46,7 @@
 
 #ifdef WORDS_BIGENDIAN
 # define SWAP(n)                                                        \
-    (((n) << 24) | (((n) & 0xff00) << 8) | (((n) >> 8) & 0xff00) | ((n) >> 24))
+    ((((n) & 0x00ff) << 24) | (((n) & 0xff00) << 8) | (((n) >> 8) & 0xff00) | (((n) >> 24) & 0x00ff)) 
 #else
 # define SWAP(n) (n)
 #endif
