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
        $idTit = null;
        $title = [];
        for($i = 0; $i < sizeof($this->cnabLine); $i++ ){
            switch(substr($this->cnabLine[$i+1],   0,   1) ){
                case "1":
                    $idTit = $i;
                    $title[$i] = [
                        'idTit'                                 => $idTit,
                        'identificacaoRegistro'                 => substr($this->cnabLine[$i+1],   0,   1),
                        'agenciaDebito'                         => trim(substr($this->cnabLine[$i+1],   1,   5)),
                        'digitoAgenciaDebito'                   => substr($this->cnabLine[$i+1],   6,   1),
                        'razaoContaCorrente'                    => trim(substr($this->cnabLine[$i+1],   7,   5)),
                        'contaCorrente'                         => trim(substr($this->cnabLine[$i+1],  12,   7)),
                        'digitoContaCorrente'                   => substr($this->cnabLine[$i+1],  19,   1),
                        'identificacaoEmpresaBeneficiariaBanco' => trim(substr($this->cnabLine[$i+1],  20,  17)),
                        'numeroControleParticipante'            => trim(substr($this->cnabLine[$i+1],  37,  25)),
                        'codigoBancoDebitadoCamaraCompensacao'  => substr($this->cnabLine[$i+1],  62,   3),
                        'campoMulta'                            => substr($this->cnabLine[$i+1],  65,   1),
                        'percentualMulta'                       => ((float) substr($this->cnabLine[$i+1], 66,  4)) / 100,
                        'identificacaoTituloBanco'              => trim(substr($this->cnabLine[$i+1],  70,  11)),
                        'digitoAutoConferenciaNumeroBancario'   => substr($this->cnabLine[$i+1],  81,   1),
                        'descontoBonificacaoDia'                => substr($this->cnabLine[$i+1],  82,  10),
                        'condicaoEmissaoPapeletaCobranca'       => substr($this->cnabLine[$i+1],  92,   1),
                        'emiteBoletoDebitoAutomatico'           => substr($this->cnabLine[$i+1],  93,   1),
                        'identificacaoOperacaoBanco'            => trim(substr($this->cnabLine[$i+1],  94,  10)),
                        'indicadorRateioCredito'                => substr($this->cnabLine[$i+1], 104,   1),
                        'enderecamentoDebitoAutomatico'         => substr($this->cnabLine[$i+1], 105,   1),
                        'quantidadePagamentos'                  => substr($this->cnabLine[$i+1], 106,   2),
                        'identificacaoOcorrencia'               => substr($this->cnabLine[$i+1], 108,   2),
                        'numeroDocumento'                       => trim(substr($this->cnabLine[$i+1], 110,  10)),
                        'dataVencimento'                        => substr($this->cnabLine[$i+1], 120,   6),
                        'valor'                                 => ((float) substr($this->cnabLine[$i+1], 126,  13)) / 100,
                        'bancoEncarregadoCobranca'              => substr($this->cnabLine[$i+1], 139,   3),
                        'agenciaDepositaria'                    => substr($this->cnabLine[$i+1], 142,   5),
                        'especieTitulo'                         => substr($this->cnabLine[$i+1], 147,   2),
                        'identificacao'                         => substr($this->cnabLine[$i+1], 149,   1),
                        'dataEmissao'                           => substr($this->cnabLine[$i+1], 150,   6),
                        'primeiraInstrucao'                     => substr($this->cnabLine[$i+1], 156,   2),
                        'segundaInstrucao'                      => substr($this->cnabLine[$i+1], 158,   2),
                        'valorCobradoPorDiaAtraso'              => ((float) substr($this->cnabLine[$i+1], 160,  13)) / 100,
                        'dataLimiteDesconto'                    => substr($this->cnabLine[$i+1], 173,   6),
                        'valorDesconto'                         => ((float) substr($this->cnabLine[$i+1], 179,  13)) / 100,
                        'valorIof'                              => ((float) substr($this->cnabLine[$i+1], 192,  13)) / 100,
                        'valorAbatimentoConcedidoOuCancelado'   => ((float) substr($this->cnabLine[$i+1], 205,  13)) / 100,
                        'identificacaoTipoInscricaoPagador'     => substr($this->cnabLine[$i+1], 218,   2),
                        'numeroInscricaoPagador'                => substr($this->cnabLine[$i+1], 220,  14),
                        'nomePagador'                           => trim(substr($this->cnabLine[$i+1], 234,  40)),
                        'enderecoCompleto'                      => trim(substr($this->cnabLine[$i+1], 274,  40)),
                        'primeiraMensagem'                      => trim(substr($this->cnabLine[$i+1], 314,  12)),
                        'cep'                                   => substr($this->cnabLine[$i+1], 326,   5),
                        'sufixoCep'                             => substr($this->cnabLine[$i+1], 331,   3),
                        'sacadorAvalistaOuSegundaMensagem'      => trim(substr($this->cnabLine[$i+1], 334,  60)),
                        'numeroSequencialRegistro'              => substr($this->cnabLine[$i+1], 394,   6),
                    ];

                break;
                case 2:
                    array_push($title[$idTit], [
                        
                    ]);
                break;
                case 7:
                    array_push($title[$idTit], [

                    ]);
                break;
                
            }
        }

        $remittanceData = [
            'identificacaoRegistro'       => substr($this->cnabLine[0],   0,   1),
            'identificacaoArquivoRemessa' => substr($this->cnabLine[0],   1,   1),
            'literalRemessa'              => substr($this->cnabLine[0],   2,   7),
            'codigoServico'               => substr($this->cnabLine[0],   9,   2),
            'literalServico'              => substr($this->cnabLine[0],  11,  15),
            'codigoEmpresa'               => substr($this->cnabLine[0],  26,  20),
            'nomeEmpresa'                 => substr($this->cnabLine[0],  46,  30),
            'numeroBanco'                 => substr($this->cnabLine[0],  76,   3),
            'nomeBanco'                   => substr($this->cnabLine[0],  79,  15),
            'dataArquivo'                 => substr($this->cnabLine[0],  94,   6),
            'branco1'                     => substr($this->cnabLine[0], 100,   8),
            'identificacaoSistema'        => substr($this->cnabLine[0], 108,   2),
            'numeroSequencialRemessa'     => substr($this->cnabLine[0], 110,   7),
            'branco2'                     => substr($this->cnabLine[0], 117, 277),
            'numeroSequencialRegistro'    => substr($this->cnabLine[0], 394,   6),
            'titles'                      => $title,

        ];
    }

    public function readReturnBradesco400(){
        
    }

    public function writeRemittanceBradesco400(){
        
    }

    public function writeReturnBradesco400(){
        
    }
}