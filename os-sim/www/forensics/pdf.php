<?php
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsForensics");

$rname = GET('name');
ossim_valid($rname, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("Report Name"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('classes/pdfReport.inc');
$pdfReport = new PdfReport($rname,"P");
$pdfReport->getPdf();
?>