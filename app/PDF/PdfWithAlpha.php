<?php

namespace App\PDF;

use setasign\Fpdi\Fpdi;

class PdfWithAlpha extends PdfWithRotation
{
    protected $extgstates = [];

    public function SetAlpha($alpha, $bm = 'Normal')
    {
        $gs = ['ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm];

        // Try to reuse existing
        $id = array_search($gs, array_map(function ($e) {
            return ['ca' => $e['ca'], 'CA' => $e['CA'], 'BM' => $e['BM']];
        }, $this->extgstates), true);

        if ($id === false) {
            $id = count($this->extgstates) + 1;
            $this->extgstates[$id] = $gs;
        }

        $this->_out("/GS{$id} gs");
    }



    protected function _enddoc()
    {
        if (!empty($this->extgstates)) {
            foreach ($this->extgstates as $k => $extgstate) {
                $this->_newobj();
                $this->_out('<<');
                $this->_out('/Type /ExtGState');
                $this->_out('/ca ' . $extgstate['ca']);
                $this->_out('/CA ' . $extgstate['CA']);
                $this->_out('/BM ' . $extgstate['BM']);
                $this->_out('>>');
                $this->_out('endobj');
                // Store the object number for use in resource dictionary
                $this->extgstates[$k]['n'] = $this->n;
            }
        }

        parent::_enddoc();
    }


    protected function _putresourcedict()
    {
        parent::_putresourcedict();

        if (!empty($this->extgstates)) {
            $this->_out('/ExtGState <<');
            foreach ($this->extgstates as $k => $extgstate) {
                $this->_out("/GS$k {$extgstate['n']} 0 R");
            }
            $this->_out('>>');
        }
    }

}
