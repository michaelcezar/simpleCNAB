<?php

class simpleCNAB {
    public $pathFile;
    public $cnabFile;
    public $cnabType = 400; //240 | 400 
    public $cnabLine;
    public $bankNumber;
    public $optionType = 'readRemittance'; //readRemittance | writeRemittance | readReturn | writeReturn

    public function getCNABFile(){
        if($this->cnabFile = fopen($this->pathFile, 'r')){
            return $this->getCNABInfo();
        } else {
            return (object) ['success' => false, 'error' => 'Não foi possível abrir o arquivo'];
        }
    }

    public function setCNABFile(){

    }

    public function getCNABInfo(){
        $getBankNumber = $this->getCNABBank();
        if($getBankNumber->success){
            switch($getBankNumber->bankNumber){
                case '237': //Banco Bradesco
                    switch($getBankNumber->cnabType){
                        case 240:
                            return (object) ['success' => false, 'error' => 'Tipo de CNAB '.$getBankNumber->cnabType.' não implementado para o banco '.$getBankNumber->bankNumber];
                        break;
                        case 400:
                            switch($this->optionType){
                                case 'readRemittance':
                                    return $this->readRemittanceBradesco400();
                                break;
                                case 'readReturn':
                                    return $this->readReturnBradesco400();
                                break;
                                default:
                                    return (object) ['success' => false, 'error' => 'Opção de operação '.$this->optionType.' não implementada para o banco '.$getBankNumber->bankNumber];
                                break;
                            }
                        break;
                        default:
                            return (object) ['success' => false, 'error' => 'Tipo de CNAB '.$getBankNumber->cnabType.' não implementado para o banco '.$getBankNumber->bankNumber];
                        break;
                    }
                break;
                default:
                    return (object) ['success' => false, 'error' => 'Banco '.$getBankNumber->bankNumber.' não suportado'];
                break;
            }
        } else {
            return $getBankNumber->error;
        }
    }

    public function getCNABBank(){
        $this->bankNumber = null;
        $this->cnabLine   =[];
        $this->cnabLine   = $this->getCNABLines();
        if($this->cnabLine != ''){
            switch($this->cnabType){
                case 240:
                    $this->bankNumber = substr($this->cnabLine[0],0,3);
                    return (object) ['success' => true, 'bankNumber' => $this->bankNumber, 'cnabType' => 240];
                break;
                case 400:
                    $this->bankNumber = substr($this->cnabLine[0],77,3);
                    return (object) ['success' => true, 'bankNumber' => $this->bankNumber, 'cnabType' => 400];
                break;
                default:
                    return (object) ['success' => false, 'error' => 'Tipo de CNAB não implementado'];
                break;
            }
        } else {
            return (object) ['success' => false, 'error' => 'Arquivo sem linhas definidas'];
        }
    }

    public function getCNABLines(){
        $cnabLines = [];
        while(!feof($this->cnabFile)){
            array_push($cnabLines, fgets($this->cnabFile, 444));
        }
        fclose($this->cnabFile);
        return $cnabLines;
    }


   
    
    public function readRemittanceBradesco400(){

    }

    public function readReturnBradesco400(){
        
    }

    public function writeRemittanceBradesco400(){
        
    }

    public function writeReturnBradesco400(){
        
    }
}