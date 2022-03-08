<?php

namespace Joy\VoyagerExport\Exports;

use Illuminate\Console\OutputStyle;
use Joy\VoyagerExport\Events\AllBreadDataExported;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeWriting;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use TCG\Voyager\Facades\Voyager;

class AllDataTypesExport implements
    WithMultipleSheets,
    WithEvents
{
    use Exportable;

    /**
     * The input.
     *
     * @var array
     */
    protected $input = [];

    /**
     * @param array $input
     */
    public function set(
        $input = []
    ) {
        $this->input = $input;
        return $this;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            // Handle by a closure.
            BeforeWriting::class => function (BeforeWriting $event) {
                event(new AllBreadDataExported($this->input));
            },
        ];
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets    = [];
        $dataTypes = Voyager::model('DataType')->get();

        foreach ($dataTypes as $dataType) {
            $exportClass = 'joy-voyager-export.export';

            if (app()->bound('joy-voyager-export.' . $dataType->slug . '.export')) {
                $exportClass = 'joy-voyager-export.' . $dataType->slug . '.export';
            }

            $export                                                           = app()->make($exportClass);
            $sheets[$dataType->getTranslatedAttribute('display_name_plural')] = $export->set(
                $dataType,
                [],
                $this->input
            );
        }

        return $sheets;
    }

    /**
     * @param  OutputStyle $output
     * @return $this
     */
    public function withOutput(OutputStyle $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @return OutputStyle
     */
    public function getConsoleOutput(): OutputStyle
    {
        if (!$this->output instanceof OutputStyle) {
            $this->output = new OutputStyle(new StringInput(''), new NullOutput());
        }

        return $this->output;
    }
}
