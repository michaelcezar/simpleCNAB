<?php

class simpleCNAB {
    public $pathFile;
    public $cnabFile;
    public $cnabType; 
    public $cnabLine;
    public $bankNumber;
    public $optionType; //readRemittance | writeRemittance | readReturn | writeReturn

    public function getCNABFile(){
        if($this->cnabFile = fopen($this->pathFile, 'r')){
            return json_encode($this->getCNABInfo());
        } else {
            return json_encode( ['success' => false, 'error' => 'Não foi possível abrir o arquivo'] );
        }
    }

    public function setCNABFile(){

    }

    public function getCNABInfo(){
        $getBankNumber = (object) $this->getCNABBank();
        if( $getBankNumber->success ){
            switch($getBankNumber->bankNumber){
                case '237': //Banco Bradesco
                    switch($getBankNumber->cnabType){
                        case 240:
                            return ['success' => false, 'error' => 'Tipo de CNAB '.$getBankNumber->cnabType.' não implementado para o banco '.$getBankNumber->bankNumber];
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
                                    return ['success' => false, 'error' => 'Opção de operação '.$this->optionType.' não implementada para o banco '.$getBankNumber->bankNumber];
                                break;
                            }
                        break;
                        default:
                            return ['success' => false, 'error' => 'Tipo de CNAB '.$getBankNumber->cnabType.' não implementado para o banco '.$getBankNumber->bankNumber];
                        break;
                    }
                break;
                default:
                    return ['success' => false, 'error' => 'Banco '.$getBankNumber->bankNumber.' não suportado'];
                break;
            }
        } else {
            return $getBankNumber->error;
        }
    }

    public function getCNABBank(){
        $this->bankNumber = null;
        $this->cnabLine   = [];
        $this->cnabLine   = $this->getCNABLines();
        if(sizeof($this->cnabLine) > 0){
            //Set CNAB Type
            if(strlen($this->cnabLine[0]) >= 400 and strlen($this->cnabLine[0]) < 443){
                $this->cnabType = 400;
            } else if(strlen($this->cnabLine[0]) >= 250 and strlen($this->cnabLine[0]) < 253){
                $this->cnabType = 250;
            } else {
                $this->cnabType = strlen($this->cnabLine[0]);
            }           
            //Set CNAB Bank
            switch($this->cnabType){
                case 240:
                    $this->bankNumber = mb_substr($this->cnabLine[0],0,3);
                    return ['success' => true, 'bankNumber' => $this->bankNumber, 'cnabType' => 240];
                break;
                case 400:
                    $this->bankNumber = mb_substr($this->cnabLine[0],76,3);
                    return ['success' => true, 'bankNumber' => $this->bankNumber, 'cnabType' => 400];
                break;
                default:
                    return ['success' => false, 'error' => 'Tipo de CNAB não implementado '.$this->cnabType];
                break;
            }
        } else {
            return ['success' => false, 'error' => 'Arquivo sem linhas definidas'];
        }
    }

    public function getCNABLines(){
        $cnabLines = [];
        while(!feof($this->cnabFile)){
            array_push($cnabLines, fgets($this->cnabFile, 444));
        }
        fclose($this->cnabFile);
        return array_map("utf8_encode",$cnabLines);
    }  

    public function readRemittanceBradesco400(){
        $header   = [];
        $title    = [];
        $trailler = [];
        $idTit    = -1;
        for($i = 0; $i < sizeof($this->cnabLine); $i++ ){
            switch(mb_substr($this->cnabLine[$i],   0,   1) ){
                case "0": //Register header
                    $header = [
                        'identificacaoRegistro'                 => mb_substr($this->cnabLine[$i],        0,   1),
                        'identificacaoArquivoRemessa'           => mb_substr($this->cnabLine[$i],        1,   1),
                        'literalRemessa'                        => mb_substr($this->cnabLine[$i],        2,   7),
                        'codigoServico'                         => mb_substr($this->cnabLine[$i],        9,   2),
                        'literalServico'                        => trim(mb_substr($this->cnabLine[$i],  11,  15)),
                        'codigoEmpresa'                         => trim(mb_substr($this->cnabLine[$i],  26,  20)),
                        'nomeEmpresa'                           => trim(mb_substr($this->cnabLine[$i],  46,  30)),
                        'numeroBanco'                           => trim(mb_substr($this->cnabLine[$i],  76,   3)),
                        'nomeBanco'                             => trim(mb_substr($this->cnabLine[$i],  79,  15)),
                        'dataArquivo'                           => $this->convertDate(trim(mb_substr($this->cnabLine[$i],       94,   6))),
                        'branco1'                               => trim(mb_substr($this->cnabLine[$i], 100,   8)),
                        'identificacaoSistema'                  => trim(mb_substr($this->cnabLine[$i], 108,   2)),
                        'numeroSequencialRemessa'               => trim(mb_substr($this->cnabLine[$i], 110,   7)),
                        'branco2'                               => trim(mb_substr($this->cnabLine[$i], 117, 277)),
                        'numeroSequencialRegistro'              => mb_substr($this->cnabLine[$i],      394,   6)
                    ];
                break;
                case "1": //Register type 1
                    $idTit++;

                    //Set CPF or CNPJ
                    if(mb_substr($this->cnabLine[$i],          218,   2) == '01'){
                        $numeroInscricaoPagador = trim(mb_substr($this->cnabLine[$i],          223,  11));
                    } else {
                        $numeroInscricaoPagador = trim(mb_substr($this->cnabLine[$i],          220,  14));
                    }

                    $title[$idTit] = [
                        'idTit'                                 => $idTit+1,
                        'identificacaoRegistroR1'               => mb_substr($this->cnabLine[$i],            0,   1),
                        'agenciaDebito'                         => trim(mb_substr($this->cnabLine[$i],       1,   5)),
                        'digitoAgenciaDebito'                   => mb_substr($this->cnabLine[$i],            6,   1),
                        'razaoContaCorrente'                    => trim(mb_substr($this->cnabLine[$i],       7,   5)),
                        'contaCorrente'                         => trim(mb_substr($this->cnabLine[$i],      12,   7)),
                        'digitoContaCorrente'                   => mb_substr($this->cnabLine[$i],           19,   1),
                        'identificacaoEmpresaBeneficiariaBanco' => trim(mb_substr($this->cnabLine[$i],      20,  17)),
                        'numeroControleParticipante'            => trim(mb_substr($this->cnabLine[$i],      37,  25)),
                        'codigoBancoDebitadoCamaraCompensacao'  => mb_substr($this->cnabLine[$i],           62,   3),
                        'campoMulta'                            => mb_substr($this->cnabLine[$i],           65,   1),
                        'percentualMulta'                       => ((float) mb_substr($this->cnabLine[$i],  66,   4)) / 100,
                        'identificacaoTituloBanco'              => trim(mb_substr($this->cnabLine[$i],      70,  11)),
                        'digitoAutoConferenciaNumeroBancario'   => mb_substr($this->cnabLine[$i],           81,   1),
                        'descontoBonificacaoDia'                => ((float) mb_substr($this->cnabLine[$i],           82,  10)) / 100,
                        'condicaoEmissaoPapeletaCobranca'       => mb_substr($this->cnabLine[$i],           92,   1),
                        'emiteBoletoDebitoAutomatico'           => mb_substr($this->cnabLine[$i],           93,   1),
                        'identificacaoOperacaoBanco'            => trim(mb_substr($this->cnabLine[$i],      94,  10)),
                        'indicadorRateioCredito'                => mb_substr($this->cnabLine[$i],          104,   1),
                        'enderecamentoDebitoAutomatico'         => mb_substr($this->cnabLine[$i],          105,   1),
                        'quantidadePagamentos'                  => mb_substr($this->cnabLine[$i],          106,   2),
                        'identificacaoOcorrencia'               => mb_substr($this->cnabLine[$i],          108,   2),
                        'numeroDocumento'                       => trim(mb_substr($this->cnabLine[$i],     110,  10)),
                        'dataVencimento'                        => $this->convertDate(trim(mb_substr($this->cnabLine[$i],          120,   6))),
                        'valor'                                 => ((float) mb_substr($this->cnabLine[$i], 126,  13)) / 100,
                        'bancoEncarregadoCobranca'              => mb_substr($this->cnabLine[$i],          139,   3),
                        'agenciaDepositaria'                    => mb_substr($this->cnabLine[$i],          142,   5),
                        'especieTitulo'                         => mb_substr($this->cnabLine[$i],          147,   2),
                        'identificacao'                         => mb_substr($this->cnabLine[$i],          149,   1),
                        'dataEmissao'                           => $this->convertDate(trim(mb_substr($this->cnabLine[$i],          150,   6))),
                        'primeiraInstrucao'                     => mb_substr($this->cnabLine[$i],          156,   2),
                        'segundaInstrucao'                      => mb_substr($this->cnabLine[$i],          158,   2),
                        'valorCobradoPorDiaAtraso'              => ((float) mb_substr($this->cnabLine[$i], 160,  13)) / 100,
                        'dataLimiteDesconto'                    => $this->convertDate(trim(mb_substr($this->cnabLine[$i],          173,   6))),
                        'valorDesconto'                         => ((float) mb_substr($this->cnabLine[$i], 179,  13)) / 100,
                        'valorIof'                              => ((float) mb_substr($this->cnabLine[$i], 192,  13)) / 100,
                        'valorAbatimentoConcedidoOuCancelado'   => ((float) mb_substr($this->cnabLine[$i], 205,  13)) / 100,
                        'identificacaoTipoInscricaoPagador'     => mb_substr($this->cnabLine[$i],          218,   2),
                        'numeroInscricaoPagador'                => $numeroInscricaoPagador,
                        'nomePagador'                           => trim(mb_substr($this->cnabLine[$i],     234,  40)),
                        'enderecoCompleto'                      => trim(mb_substr($this->cnabLine[$i],     274,  40)),
                        'primeiraMensagem'                      => trim(mb_substr($this->cnabLine[$i],     314,  12)),
                        'cep'                                   => mb_substr($this->cnabLine[$i],          326,   5),
                        'sufixoCep'                             => mb_substr($this->cnabLine[$i],          331,   3),
                        'sacadorAvalistaOuSegundaMensagem'      => trim(mb_substr($this->cnabLine[$i],     334,  60)),
                        'numeroSequencialRegistroR1'            => mb_substr($this->cnabLine[$i],          394,   6),
                        'chaveNFE'                              => str_replace("\r\n", '', mb_substr($this->cnabLine[$i], 400,  44)),
                    ];
                break;
                case "2": //Register type 2
                    if(isset($title[$idTit])){
                        $title[$idTit]['identificacaoRegistroR2']               = mb_substr($this->cnabLine[$i],            0,   1);
                        $title[$idTit]["mensagem1"]                             = trim(mb_substr($this->cnabLine[$i],       1,  80));
                        $title[$idTit]["mensagem2"]                             = trim(mb_substr($this->cnabLine[$i],      81,  80));
                        $title[$idTit]["mensagem3"]                             = trim(mb_substr($this->cnabLine[$i],     161,  80));
                        $title[$idTit]["mensagem4"]                             = trim(mb_substr($this->cnabLine[$i],     241,  80));
                        $title[$idTit]["dataLimiteConcessaoDesconto2"]          = $this->convertDate(trim(mb_substr($this->cnabLine[$i],          321,   6)));
                        $title[$idTit]["valorDesconto2"]                        = ((float) mb_substr($this->cnabLine[$i], 327,  13)) / 100;
                        $title[$idTit]["dataLimiteConcessaoDesconto3"]          = $this->convertDate(trim(mb_substr($this->cnabLine[$i],          340,   6)));
                        $title[$idTit]["valorDesconto3"]                        = ((float) mb_substr($this->cnabLine[$i], 346,  13)) / 100;
                        $title[$idTit]["reservaR2"]                             = mb_substr($this->cnabLine[$i],          359,   7);
                        $title[$idTit]["carteiraR2"]                            = mb_substr($this->cnabLine[$i],          366,   3);
                        $title[$idTit]["agenciaR2"]                             = trim(mb_substr($this->cnabLine[$i],     369,   5));
                        $title[$idTit]["contaCorrenteR2"]                       = trim(mb_substr($this->cnabLine[$i],     374,   7));
                        $title[$idTit]["digitoCCR2"]                            = trim(mb_substr($this->cnabLine[$i],     381,   1));
                        $title[$idTit]["nossoNumeroR2"]                         = trim(mb_substr($this->cnabLine[$i],     382,  11));
                        $title[$idTit]["dacNossoNumeroR2"]                      = mb_substr($this->cnabLine[$i],          393,   1);
                        $title[$idTit]["numeroSequencialRegistroR2"]            = mb_substr($this->cnabLine[$i],          394,   6);
                    }
                break;
                case "7": //Register type 7
                    if(isset($title[$idTit])){
                        $title[$idTit]['identificacaoRegistroR7']               = mb_substr($this->cnabLine[$i],            0,    1);
                        $title[$idTit]["enderecoSacadorAvalista"]               = trim(mb_substr($this->cnabLine[$i],      1,    45));
                        $title[$idTit]["cepSacadorAvalista"]                    = trim(mb_substr($this->cnabLine[$i],     46,     5));
                        $title[$idTit]["sufixoCepSacadorAvalista"]              = trim(mb_substr($this->cnabLine[$i],     51,     3));
                        $title[$idTit]["cidadeSacadorAvalista"]                 = trim(mb_substr($this->cnabLine[$i],     54,    20));
                        $title[$idTit]["ufSacadorAvalista"]                     = trim(mb_substr($this->cnabLine[$i],     74,     2));
                        $title[$idTit]["reservaR7"]                             = trim(mb_substr($this->cnabLine[$i],     76,   290));
                        $title[$idTit]["carteiraR7"]                            = trim(mb_substr($this->cnabLine[$i],    366,     3));
                        $title[$idTit]["agenciaR7"]                             = trim(mb_substr($this->cnabLine[$i],    369,     5));
                        $title[$idTit]["contaCorrenteR7"]                       = trim(mb_substr($this->cnabLine[$i],    374,     7));
                        $title[$idTit]["digitoCCR7"]                            = trim(mb_substr($this->cnabLine[$i],    381,     1));
                        $title[$idTit]["nossoNumeroR7"]                         = trim(mb_substr($this->cnabLine[$i],    382,    11));
                        $title[$idTit]["dacNossoNumeroR7"]                      = trim(mb_substr($this->cnabLine[$i],    393,     1));
                        $title[$idTit]["numeroSequencialRegistroR7"]            = mb_substr($this->cnabLine[$i],         394,     6);
                    }
                break;
                case "9": //Register trailler
                    $trailler = [
                        'identificacaoRegistroR9'               => mb_substr($this->cnabLine[$i],            0,    1),
                        "brancoR9"                              => trim(mb_substr($this->cnabLine[$i],       1,  393)),
                        "numeroSequencialRegistroR7"            => mb_substr($this->cnabLine[$i],          394,    6)
                    ];
                break;
            }
        }

        if( sizeof($header) > 0 and  sizeof($title) > 0 and sizeof($trailler) > 0 ){
            return [
                'success' => true,
                'remittanceData' => [
                    'header'   => ($header),
                    'titles'   => ($title),
                    'trailler' => ($trailler)
                ]
            ];
        } else {
            return ['success' => false, 'error' => 'Arquivo não possui header, registros do tipo 1 ou trailler'];
        }        
    }

    public function readReturnBradesco400(){
        
    }

    public function writeRemittanceBradesco400(){
        
    }

    public function writeReturnBradesco400(){
        
    }

    public function convertDate($dateToConvert){
        if($dateToConvert != '' and $dateToConvert != '000000'){
            return (DateTime::createFromFormat('dmy', $dateToConvert))->format('Y-m-d');
        } else {
            return null;
        }
    }
}