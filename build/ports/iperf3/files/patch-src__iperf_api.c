--- src/iperf_api.c.orig	2015-10-17 02:01:09.000000000 +0900
+++ src/iperf_api.c	2015-11-08 18:28:09.914074000 +0900
@@ -2532,7 +2532,7 @@
 	    if (test->json_output)
 		cJSON_AddItemToArray(json_interval_streams, iperf_json_printf("socket: %d  start: %f  end: %f  seconds: %f  bytes: %d  bits_per_second: %f  retransmits: %d  snd_cwnd:  %d  rtt:  %d  omitted: %b", (int64_t) sp->socket, (double) st, (double) et, (double) irp->interval_duration, (int64_t) irp->bytes_transferred, bandwidth * 8, (int64_t) irp->interval_retrans, (int64_t) irp->snd_cwnd, (int64_t) irp->rtt, irp->omitted));
 	    else {
-		unit_snprintf(cbuf, UNIT_LEN, irp->snd_cwnd, 'A');
+		unit_snprintf(cbuf, UNIT_LEN, (unsigned int)irp->snd_cwnd, 'A');
 		iprintf(test, report_bw_retrans_cwnd_format, sp->socket, st, et, ubuf, nbuf, irp->interval_retrans, cbuf, irp->omitted?report_omitted:"");
 	    }
 	} else {
