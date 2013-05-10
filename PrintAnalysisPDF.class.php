<?php
/*************************************************
 * Handmade by MilkmanMedia - www.MilkmanMedia.de
 *************************************************
 * Class PrintAnalysisPDF (v0.3)
 *
 * version history
 *  0.3: stopped deletion of sourceimage and prepared new feature for deleting temp images via param  
 *  0.2: added support for multiple paperformats e.g. fullsize or DIN-A3 to be able to generate print optimized PDF
 *  0.1: basic functionality for splitting sourceFile into several parts to fit in printable PDF size
 *
 * @sourceFile: image that should be printed (must be png)
 * @paperFormat: format the resulting PDf should be in
 * 
 * usage: 
 * $printAnalysis = new PrintAnalysisPDF('graph.png');
 * $printAnalysis->printPDF();
 */

class PrintAnalysisPDF {
  private $sourceFile;
  private $paperFormat;
  private $paperMeasurments = Array(
    "A4" => Array(
      "width" => 793.7, // default A4 paperwidth in px at 96dpi
      "height" => 1122.52 // default A4 paperheight in px at 96dpi
    ),
    "FULL" => Array(
      "width" => false,
      "height" => false
    )
  );
  private $paperWidth;
  private $paperHeight;
  private $paperInnerWidth;
  private $paperInnerHeight;
  private $paperOuterWidth;
  private $paperOuterHeight;
  private $paperMargin = array(
    "top" => 20,
    "left" => 20,
    "bottom" => 20,
    "right" => 20
  );
  private $imageWidth;
  private $imageHeight;
  private $resizeMulitple;
  private $imagesToPrint = array();
  private $deleteTmpImages = true;
  
  public function __construct($sourceFile, $paperFormat = "A4"){
    if(file_exists($sourceFile)){
      $this->setSourceFile($sourceFile);
      $this->setPaperFormat($paperFormat);
      $this->setPaperSize();
      $this->setDimensions();
    	
      ($this->imageHeight > $this->paperOuterHeight) ? ($this->createImageSlices()) : ($this->imagesToPrint[0] = $this->sourceFile);
    }
    else{
      $this->error("file '".$sourceFile."' does not exist.");
    }
  }

  private function setSourceFile($file){
    $this->sourceFile = $file;
  }
  
  private function setPaperFormat($paperFormat){
    $this->paperFormat = $paperFormat;
  }

  private function setPaperSize(){
    $this->paperWidth = $this->paperMeasurments[$this->paperFormat]["width"];
    $this->paperHeight = $this->paperMeasurments[$this->paperFormat]["height"];
  }

  private function setDimensions(){
    $this->setImageDimensions();
    $this->setPaperDimensions();
  }
  
  private function setImageDimensions(){
    list($this->imageWidth, $this->imageHeight) = getimagesize($this->sourceFile);

    if(!$this->paperWidth) $this->paperWidth = $this->imageWidth;
    if(!$this->paperHeight) $this->paperHeight = $this->imageHeight;
    
    $this->resizeMulitple = (($this->paperWidth > $this->imageWidth) ? ($this->paperWidth / $this->imageWidth) : ($this->imageWidth / $this->paperWidth));
  }
  
  private function setPaperDimensions(){
    $this->paperInnerWidth = $this->paperWidth * $this->resizeMulitple;
    $this->paperInnerHeight = $this->paperHeight * $this->resizeMulitple;
    $this->paperOuterWidth = $this->paperInnerWidth + $this->paperMargin["left"] + $this->paperMargin["right"];
    $this->paperOuterHeight = $this->paperInnerHeight + $this->paperMargin["top"] + $this->paperMargin["bottom"];
  }
  
  private function createImageSlices(){
    for($i = 0; $i < $this->imageHeight; $i += $this->paperInnerHeight){
      $newFilename = "graph-".$i.".png";
      $this->imagesToPrint[] = $newFilename;
      $source = imagecreatefrompng($this->sourceFile);
      $tmp = imagecreate($this->imageWidth, $this->paperInnerHeight);
      // if part of the image is not in  full pageheight we need to calculate the correct height of the part - otherwise the background is filled with black ... 
      $heightHelper = (($i + $this->paperInnerHeight > $this->imageHeight) ? $this->imageHeight-$i : $this->paperInnerHeight);
      
      imagecopyresized($tmp, $source, 0, 0, 0, $i, $this->imageWidth, $heightHelper, $this->imageWidth, $heightHelper);
      imagepng($tmp, $newFilename);
    }
  }
  
  public function printPDF(){
    if(!empty($this->imagesToPrint) && class_exists("FPDF")){
      $pdf = new FPDF("P", "pt");
    
      foreach ($this->imagesToPrint as $imageName){
        $pdf->AddPage("P", array($this->paperOuterWidth, $this->paperOuterHeight));
        $pdf->Image($imageName, $this->paperMargin["left"], $this->paperMargin["top"], $this->imageWidth);	
        
        if($this->deleteTmpImages && count($this->imagesToPrint) > 1){          
          // there is no need to set up an extra function for deleting the split images
          unlink($imageName);
        }
      }
      
      $pdf->Output();
    }
    else{
      (empty($this->imagesToPrint)) ? ($this->error("no images to print.")) : "";
      (!class_exists("FPDF")) ? ($this->error("class 'FPDF' is required for PDF printing but does not exist.")) : "";
    }
  }
  
  private function error($text){
    echo $text."<br>";
  }
}

?>
