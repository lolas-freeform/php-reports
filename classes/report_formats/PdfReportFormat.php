<?php
class PdfReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		//always use cache for CSV reports
		$report->use_cache = true;
		
		$file_name = preg_replace(array('/[\s]+/','/[^0-9a-zA-Z\-_\.]/'),array('_',''),$report->options['Name']);
		
		header("Content-type: application/pdf");
		header("Content-Disposition: attachment; filename=".$file_name.".pdf");
		header("Pragma: no-cache");
		header("Expires: 0");

    //determine if this is an asynchronous report or not
    $report->async = false;

    $template = 'pdf/report';


    try {
      $additional_vars = array();
      if(isset($request->query['no_charts'])) $additional_vars['no_charts'] = true;

      $html = $report->renderReportPage($template,$additional_vars);
    }
    catch(Exception $e) {
      if(isset($request->query['content_only'])) {
        $template = 'html/blank_page';
      }

      $vars = array(
        'title'=>$report->report,
        'header'=>'<h2>There was an error running your report</h2>',
        'error'=>$e->getMessage(),
        'content'=>"<h2>Report Query</h2>".$report->options['Query_Formatted'],
      );

      $html = $report->renderReportPage($template,$additional_vars);
    }
    // disable DOMPDF's internal autoloader since we are using Composer
    define('DOMPDF_ENABLE_AUTOLOAD', false);
    require_once("vendor/dompdf/dompdf/dompdf_config.inc.php");

    $dompdf = new DOMPDF();
    $dompdf->load_html($html);
    $dompdf->render();
    $dompdf->stream($file_name);
	}
}
