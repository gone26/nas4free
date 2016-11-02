--- src/mod_scgi.c.orig	2016-10-31 14:16:40.000000000 +0100
+++ src/mod_scgi.c	2016-11-02 15:28:39.000000000 +0100
@@ -2699,7 +2699,7 @@
 		ct_len = buffer_string_length(ext->key);
 
 		/* check _url_ in the form "/scgi_pattern" */
-		if (extension->key->ptr[0] == '/') {
+		if (ext->key->ptr[0] == '/') {
 			if (ct_len <= uri_path_len
 			    && 0 == strncmp(con->uri.path->ptr, ext->key->ptr, ct_len)) {
 				extension = ext;
