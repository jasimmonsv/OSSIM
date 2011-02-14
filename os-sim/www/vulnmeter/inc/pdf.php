<?
require('diag.php');

class PDF extends PDF_Diag
{
//This Function creates a table formated for the Vulnerability Scanner.  It allows the last column to be multilined
//currently boarderType does nothing
	function PrintTable($header,$data,$w,$headFillColor,$headTextColor, $fillColor, $textColor, $lineColor, $boarderType, $h, $ip, $links_to_vulns)
	{
//currently boarder does nothing
		$boarder="";
      global $logh;

		$numRows = count($header) - 1;
		
		//Colors, line width and bold font
		
		$this->SetFillColor($headFillColor[0], $headFillColor[1], $headFillColor[2]);
		$this->SetTextColor($headTextColor);
		$this->SetDrawColor($lineColor[0], $lineColor[1], $lineColor[2]);
		$this->SetLineWidth(.3);
		$this->SetFont('','B');
		//Makes Header

      $this->SetFont('Arial', '', 10);
      // skip the host in the header
		for($i=0;$i<count($header);$i++)
		//for($i=1;$i<count($header);$i++)
			$this->Cell($w[$i],$h,$header[$i],1,0,'C',1);
		$this->Ln();
		//Color and font restoration
		$this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
		$this->SetTextColor($textColor);
      $this->SetFont('Arial', '', 8);

		//Makes Table
		$fill=1;
		$this->wPt = $this->fwPt;
		$this->hPt=$this->fhPt;
		$this->w=$this->fw;
		$this->h=$this->fh;
		foreach($data as $row)
		{
            if ($row[0]=="Serious") {
                if($links_to_vulns[$ip]["1"]!="") {
                    $this->SetLink($links_to_vulns[$ip]["1"],$this->GetY());
                    $links_to_vulns[$ip]["1"]="";
                }
                $this->SetFillColor(255, 205, 255);
            }
            else if($row[0]=="High") {
                if($links_to_vulns[$ip]["2"]!="") {
                    $this->SetLink($links_to_vulns[$ip]["2"],$this->GetY());
                    $links_to_vulns[$ip]["2"]="";
                }
                $this->SetFillColor(255, 219, 219);
            }
            else if($row[0]=="Medium") {
                if($links_to_vulns[$ip]["3"]!="") {
                    $this->SetLink($links_to_vulns[$ip]["3"],$this->GetY());
                    $links_to_vulns[$ip]["3"]="";
                }
                $this->SetFillColor(255, 242, 131);
            }
            else if($row[0]=="Low") {
                if($links_to_vulns[$ip]["6"]!="") {
                    $this->SetLink($links_to_vulns[$ip]["6"],$this->GetY());
                    $links_to_vulns[$ip]["6"]="";
                }
                $this->SetFillColor(255, 255, 192);
            }
            else {
                if($links_to_vulns[$ip]["7"]!="") {
                    $this->SetLink($links_to_vulns[$ip]["7"],$this->GetY());
                    $links_to_vulns[$ip]["7"]="";
                }
                $this->SetFillColor(255, 255, 227);
            }
//         $ip=$row[0];
         //$logh->log("ip: $ip",PEAR_LOG_INFO);
//         if($oldip!=$ip) {
//            $oldip=$ip;
            // print the row for the host
//            $this->SetFont('','B',10);
//			   $this->Cell(array_sum($w),$h,"Host: ".$ip,'LR',1,'L',$fill);
//            $fill=!$fill;
//         } else {
//         }
		   //$i = 0;
			for($i = 0; $i < count($w); $i++)
			//for($i = 1; $i < count($w); $i++)
			{

				if($i == count($w) - 1) //last column can have multiple lines
				{
					$this->MultiCellFill($w[$i],$h,$row[$i],'LR', 'L',$fill, $w);
				}
				else
				{
               $this->SetFont('','B',9);
               //$row[$i]=str_replace(' ','\n',$row[$i]);
//               $logh->log("row[i]: $row[$i]",PEAR_LOG_INFO);
					$this->Cell($w[$i],$h,$row[$i],'LR',0,'C',$fill);
               //if(strpos($row[$i],"\n")) {
               //   $this->MultiCell($w[$i],$h,$row[$i],'LR','L',$fill);
               //} else {
					//   $this->Cell($w[$i],$h,$row[$i],'LR',0,'L',$fill);
               //}
               $this->SetFont('','',8);
				}
			}
			//$fill=!$fill;
			$this->Cell(array_sum($w), .3, '', 'T', 1);
		}
		
		$this->Cell(array_sum($w), $h, '', 'T');
		
		
	}

   // create the table for the Open Ports
	function OpenPortTable($header,$data,$w,$headFillColor,$headTextColor, $fillColor, $textColor, $lineColor, $boarderType, $h , $lo)
	{
//currently boarder does nothing
		$boarder="";
      global $logh;

		$numRows = count($header) - 1;

      // set a left offset, if defined
      $origX = $this->GetX();
      $this->SetX($lo);
		
		//Colors, line width and bold font
		
		$this->SetFillColor($headFillColor[0], $headFillColor[1], $headFillColor[2]);
		$this->SetTextColor($headTextColor);
		$this->SetDrawColor($lineColor[0], $lineColor[1], $lineColor[2]);
		$this->SetLineWidth(.3);
		$this->SetFont('','B');
		//Makes Header

      $this->SetFont('Arial', '', 10);
		for($i=0;$i<count($header);$i++)
         $this->Cell($w[$i],$h,$header[$i],1,0,'C',1);
		$this->Ln();
      $this->SetX($lo);
		//Color and font restoration
		$this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
		$this->SetTextColor($textColor);
      $this->SetFont('Arial', '', 8);

		//Makes Table
		$fill=0;
		$this->wPt = $this->fwPt;
		$this->hPt=$this->fhPt;
		$this->w=$this->fw;
		$this->h=$this->fh;
		foreach($data as $row)
		{
		   //$i = 0;
			for($i = 0; $i < count($w); $i++)
			{
            if($i % 2) {
					$this->Cell($w[$i],$h,$row[$i],'LR',1,'C',$fill);
            } else {
               $this->SetX($lo);
					$this->Cell($w[$i],$h,$row[$i],'LR',0,'C',$fill);
            }
			}
			$fill=!$fill;
         $this->SetX($lo);
			$this->Cell(array_sum($w), .3, '', 'T', 1);
		}
		
	}

	//Writes cell as multiline and fills in other columns so table remains balanced
	//Much of the code taken from MultiCell included with FPDF	
	function MultiCellFill($w,$h,$txt,$border=0,$align='J',$fill, $wArray)
	{
//currently boarder does nothing
		$boarder="";
		
		$cw=&$this->CurrentFont['cw'];
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 and $s[$nb-1]=="\n")
			$nb--;
		$b=0;
		if($border)
		{
			if($border==1)
			{
				$border='LTRB';
				$b='LRT';
				$b2='LR';
			}
			else
			{
				$b2='';
				if(is_int(strpos($border,'L')))
					$b2.='L';
				if(is_int(strpos($border,'R')))
					$b2.='R';
				$b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
			}
		}
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$ns=0;
		$nl=1;
		while($i<$nb)
		{
			//Get next character
			$c=$s{$i};
			if($c=="\n")
			{
				//Explicit line break
				if($this->ws>0)
				{
					$this->ws=0;
					$this->_out('0 Tw');
				}
				$this->Cell($w,$h,substr($s,$j,$i-$j),$b,1,$align,$fill);
				$this->fixEmpty($wArray, $h, $boarder, $fill);
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$ns=0;
				$nl++;
				if($border and $nl==2)
					$b=$b2;
				continue;
			}
			if($c==' ')
			{
				$sep=$i;
				$ls=$l;
				$ns++;
			}
			$l+=$cw[$c];
			if($l>$wmax)
			{
				//Automatic line break
				if($sep==-1)
				{
					if($i==$j)
						$i++;
					if($this->ws>0)
					{
						$this->ws=0;
						$this->_out('0 Tw');
					}
					$this->Cell($w,$h,substr($s,$j,$i-$j),$b,1,$align,$fill);
					$this->fixEmpty($wArray, $h, $boarder, $fill);
				}
				else
				{
					if($align=='J')
					{
						$this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
						$this->_out(sprintf('%.3f Tw',$this->ws*$this->k));
					}
					$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,1,$align,$fill);
					$this->fixEmpty($wArray, $h, $boarder, $fill);
					$i=$sep+1;
				}
				$sep=-1;
				$j=$i;
				$l=0;
				$ns=0;
				$nl++;
				if($border and $nl==2)
					$b=$b2;
			}
			else
				$i++;
		}
		//Last chunk
		if($this->ws>0)
		{
			$this->ws=0;
			$this->_out('0 Tw');
		}
		if($border and is_int(strpos($border,'B')))
			$b.='B';
		$this->Cell($w,$h,substr($s,$j,$i-$j),$b,1,$align,$fill);
		//$this->fixEmpty($wArray, $h, $boarder, $fill);
		$this->x=$this->lMargin;
	}
	//fills in first lines so table remains balanced
	function fixEmpty($w, $h, $boarder, $fill)
	{
		for($i = 0; $i < count($w) - 1; $i++)
		//for($i = 1; $i < count($w) - 1; $i++)
		{
			$this->Cell($w[$i], $h, "  ", 'LR', 0, 'L', $fill);
		}		
	}
}
?>
