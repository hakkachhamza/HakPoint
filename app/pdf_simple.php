<?php
/**
 * Small dependency-free PDF writer used by GLOBAL ENERGIE EVENTS.
 * Supports valid PDF 1.4 output, JPEG logos, and multiple A4 pages.
 * Note: this minimal writer is for Latin/French text. Arabic/RTL still needs a Unicode PDF library.
 */
class SimplePdf {
    private array $objects = [];
    private array $pages = [''];
    private int $currentPage = 0;
    private int $fontObj = 0;
    private int $w = 595;
    private int $h = 842;
    private array $images = [];

    private function enc($s): string {
        $s = (string)$s;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
            if ($converted !== false) $s = $converted;
        }
        return str_replace(['\\','(',')',"\r"], ['\\\\','\\(','\\)',''], $s);
    }
    private function addObj(string $body): int { $this->objects[] = $body; return count($this->objects); }
    private function y($y): float { return $this->h - (float)$y; }
    private function write(string $s): void { $this->pages[$this->currentPage] .= $s; }

    public function addPage(): void { $this->pages[]=''; $this->currentPage=count($this->pages)-1; }
    public function pageNo(): int { return $this->currentPage + 1; }
    public function pageCount(): int { return count($this->pages); }

    public function text($x, $y, $txt, $size = 10, $bold = false, $align = 'L') {
        $txt = $this->enc($txt);
        $color = $bold ? '0.05 0.05 0.18 rg' : '0 0 0 rg';
        $this->write("BT /F1 ".((float)$size)." Tf $color ".((float)$x)." ".$this->y($y)." Td ($txt) Tj ET\n");
    }
    public function textRight($x, $y, $txt, $size = 10, $bold = false) {
        $raw = (string)$txt;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$raw);
            if ($converted !== false) $raw = $converted;
        }
        $approx = strlen($raw) * ((float)$size) * 0.48;
        $this->text((float)$x - $approx, $y, $txt, $size, $bold);
    }
    public function line($x1, $y1, $x2, $y2, $gray = 0.55, $width = 0.6) {
        $this->write(((float)$gray)." G ".((float)$width)." w ".((float)$x1)." ".$this->y($y1)." m ".((float)$x2)." ".$this->y($y2)." l S\n");
    }
    public function rect($x, $y, $w, $h, $fill = false, $gray = 0.90) {
        $x=(float)$x; $y=(float)$y; $w=(float)$w; $h=(float)$h;
        if ($fill) $this->write(((float)$gray)." g $x ".($this->h-$y-$h)." $w $h re f\n");
        else $this->write("0.55 G 0.7 w $x ".($this->h-$y-$h)." $w $h re S\n");
    }
    public function imageJpeg($file, $x, $y, $w, $h) {
        if (!is_file($file)) return false;
        $info = @getimagesize($file);
        if (!$info || ($info[2] ?? 0) !== IMAGETYPE_JPEG) return false;
        $name = 'Im'.(count($this->images) + 1);
        $this->images[] = ['name'=>$name,'file'=>$file,'width'=>(int)$info[0],'height'=>(int)$info[1]];
        $x=(float)$x; $y=(float)$y; $w=(float)$w; $h=(float)$h;
        $this->write("q $w 0 0 $h $x ".($this->h-$y-$h)." cm /$name Do Q\n");
        return true;
    }

    public function save($file) {
        $dir = dirname($file); if (!is_dir($dir)) mkdir($dir, 0777, true);
        $this->objects=[];
        $this->fontObj = $this->addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $xobjects = '';
        foreach ($this->images as $k=>$img) {
            $data = file_get_contents($img['file']); if ($data === false) continue;
            $obj = $this->addObj("<< /Type /XObject /Subtype /Image /Width ".$img['width']." /Height ".$img['height']." /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($data)." >>\nstream\n".$data."\nendstream");
            $this->images[$k]['obj']=$obj;
            $xobjects .= ' /'.$img['name'].' '.$obj.' 0 R';
        }
        $resources = '<< /Font << /F1 '.$this->fontObj.' 0 R >>';
        if ($xobjects !== '') $resources .= ' /XObject <<'.$xobjects.' >>';
        $resources .= ' >>';
        $pagesObj = count($this->objects) + (count($this->pages) * 2) + 1;
        $kids=[];
        foreach($this->pages as $stream){
            $contentObj=$this->addObj("<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream");
            $pageObj=$this->addObj('<< /Type /Page /Parent '.$pagesObj.' 0 R /MediaBox [0 0 '.$this->w.' '.$this->h.'] /Resources '.$resources.' /Contents '.$contentObj.' 0 R >>');
            $kids[]=$pageObj.' 0 R';
        }
        $this->addObj('<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>');
        $catalog = $this->addObj('<< /Type /Catalog /Pages '.$pagesObj.' 0 R >>');
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets=[0];
        foreach($this->objects as $i=>$obj){ $offsets[]=strlen($pdf); $n=$i+1; $pdf.="$n 0 obj\n$obj\nendobj\n"; }
        $xref=strlen($pdf); $count=count($this->objects)+1;
        $pdf.="xref\n0 $count\n0000000000 65535 f \n";
        for($i=1;$i<$count;$i++) $pdf.=sprintf("%010d 00000 n \n", $offsets[$i]);
        $pdf.="trailer\n<< /Size $count /Root $catalog 0 R >>\nstartxref\n$xref\n%%EOF";
        file_put_contents($file,$pdf);
    }
}
