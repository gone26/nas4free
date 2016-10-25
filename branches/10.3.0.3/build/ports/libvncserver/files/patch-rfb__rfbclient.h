--- rfb/rfbclient.h.orig	2014-10-21 17:57:11.000000000 +0200
+++ rfb/rfbclient.h	2016-09-23 14:00:54.000000000 +0200
@@ -47,13 +47,13 @@
     (*(char *)&client->endianTest ? ((((s) & 0xff) << 8) | (((s) >> 8) & 0xff)) : (s))
 
 #define rfbClientSwap32IfLE(l) \
-    (*(char *)&client->endianTest ? ((((l) & 0xff000000) >> 24) | \
+    (*(char *)&client->endianTest ? ((((l) >> 24) & 0x000000ff) | \
 			     (((l) & 0x00ff0000) >> 8)  | \
 			     (((l) & 0x0000ff00) << 8)  | \
 			     (((l) & 0x000000ff) << 24))  : (l))
 
 #define rfbClientSwap64IfLE(l) \
-    (*(char *)&client->endianTest ? ((((l) & 0xff00000000000000ULL) >> 56) | \
+    (*(char *)&client->endianTest ? ((((l) >> 56 ) & 0x00000000000000ffULL) | \
 			     (((l) & 0x00ff000000000000ULL) >> 40)  | \
 			     (((l) & 0x0000ff0000000000ULL) >> 24)  | \
 			     (((l) & 0x000000ff00000000ULL) >> 8)  | \
