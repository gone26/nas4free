--- src/msmtp.c.orig	2016-04-09 21:59:04.000000000 +0200
+++ src/msmtp.c	2016-10-29 15:15:33.000000000 +0200
@@ -1626,6 +1626,7 @@
                 list_insert(recipients, resent_recipients->data);
                 recipients = recipients->next;
             }
+	    list_free(resent_recipients);
         }
         else
         {
@@ -1637,7 +1638,10 @@
                 list_insert(recipients, normal_recipients->data);
                 recipients = recipients->next;
             }
+            list_free(normal_recipients);
         }
+        normal_recipients_list = NULL;
+        resent_recipients_list = NULL;
     }
 
     if (ferror(mailf))
@@ -1649,9 +1653,12 @@
     return EX_OK;
 
 error_exit:
-    if (recipients)
+    if (normal_recipients_list)
     {
         list_xfree(normal_recipients_list, free);
+    }
+    if (resent_recipients_list)
+    {
         list_xfree(resent_recipients_list, free);
     }
     if (from)
