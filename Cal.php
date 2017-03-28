<?php

class Cal
{
    const CAL_URL = "http://data.bordeaux-metropole.fr/preview.ajax.php?op=preview&layer_gid=489";
    const FILE_URL = "";
    const FINAL_URL = "";

    /**
     * @var string
     */
    private $context;

    /**
     * @var json
     */
    private $file;

    public function __construct()
    {
        $this->context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "Accept-language: en\r\n" . "Cookie: foo=bar\r\n"
            ]
        ]);
    }

    /**
     * Get the file
     */
    public function getFile()
    {
        if (is_null($this->file)) {
            $this->file = file_get_contents(Cal::CAL_URL, false, $this->context);
        }

        return json_decode($this->file);
    }

    /**
     * Build ics
     */
    public function buildCalendar()
    {
        $data = $this->getFile();

        if (count($data) > 0) {
            unset($data[0]);

            $ics = "BEGIN:VCALENDAR".chr(10);
            $ics = $ics."VERSION:2.0".chr(10);
            $ics = $ics."PRODID:-//LFP/iCal 3.0//FR".chr(10);

            foreach ($data as $value) {
                $ics = $ics."BEGIN:VEVENT".chr(10);

                $monthAndDay = substr($value[1], 3, 2).substr($value[1], 0, 2);
                $currentYear = substr($value[1], 6, 4);
                $closedHour = str_replace(':', 'h', $value[2]);
                $openedHour = str_replace(':', 'h', $value[3]);

                $openedDate = new DateTime($currentYear.$monthAndDay.$value[2], new DateTimeZone('Europe/Paris'));
                $openedDate->sub(new DateInterval('PT2H'));
                $openedDate = $openedDate->format('Ymd\THis\Z');

                $closedDate = new DateTime($currentYear.$monthAndDay.$value[3], new DateTimeZone('Europe/Paris'));
                $closedDate->sub(new DateInterval('PT2H'));
                $closedDate = $closedDate->format('Ymd\THis\Z');

                $ics = $ics."DTSTART:".$openedDate.chr(10);
                $ics = $ics."DTEND:".$closedDate.chr(10);
                $ics = $ics."SUMMARY:Pont Chaban Delmas fermé - ".$value[0]." à ".$closedHour.".".chr(10);
                $ics = $ics."LOCATION:Pont Chaban Delmas".chr(10);
                $ics = $ics."DESCRIPTION:Pont Chaban Delmas fermé - ".$value[0]." à ".$closedHour.". Ré-ouverture à ".$openedHour.".".chr(10);
                $ics = $ics."END:VEVENT".chr(10);
            }

            $ics = $ics."END:VCALENDAR".chr(10);

            return $this->writeFile($ics);
        }
    }

    /**
     * Write file
     *
     * @param string $content
     * @param bool $download
     */
    public function writeFile($content, $download = true)
    {
        $fileName = "pont.ics";
        $fileUrl = sprintf('%s%s', Cal::FINAL_URL, $fileName);

        $myFile = fopen(sprintf("%s%s", Cal::FILE_URL, $fileName), "w");
        fwrite($myFile, $content);
        fclose($myFile);

        if ($download) {
            return $this->download($fileName, $fileUrl);            
        }
    }

    /**
     * Download action
     */
    public function download($fileName, $fileUrl)
    {
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header(sprintf("Content-disposition: attachment; filename=%s", $fileName)); 
        
        return readfile($fileUrl);
    }
}
