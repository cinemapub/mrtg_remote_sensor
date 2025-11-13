<?php

declare(strict_types=1);

namespace MrtgSensor\Sensor;

final class MrtgFormatter
{
    public static function formatOutput(SensorResult $result, bool $withConfig = false): string
    {
        $output = sprintf(
            "%s\n%s\n%s\n%s\n",
            trim((string)$result->value1),
            trim((string)$result->value2),
            trim($result->uptime),
            trim($result->server)
        );

        if (!$withConfig) {
            $output .= "url_source={$result->cfgUrl}\n";
            $output .= 'time_server=' . date('c') . "\n";
            $output .= "id_counter={$result->mrtgName}\n";
            $output .= "version_api={$result->version}\n";
        } else {
            $output .= self::formatConfig($result);
        }

        return $output;
    }

    private static function formatConfig(SensorResult $result): string
    {
        $name = str_replace('%', 'p', $result->mrtgName);

        $config = "#####################################\n";
        $config .= "#### MRTG CONFIG {$name} ####\n";
        $config .= "#####################################\n";
        $config .= "Target[{$name}]: `curl -s \"{$result->url}\"`\n";
        $config .= "Title[{$name}]: {$result->description}\n";
        $config .= "PageTop[{$name}]: <h1>{$result->description}</h1>\n";

        if ($result->name1) {
            $config .= "LegendI[{$name}]: {$result->name1}\n";
            $config .= "Legend1[{$name}]: {$result->name1}\n";
            $config .= "Legend3[{$name}]: ↑ {$result->name1}\n";
        }

        if ($result->name2) {
            $config .= "LegendO[{$name}]: {$result->name2}\n";
            $config .= "Legend2[{$name}]: {$result->name2}\n";
            $config .= "Legend4[{$name}]: ↑ {$result->name2}\n";
        }

        $config .= "YLegend[{$name}]: {$result->mrtgUnit}\n";
        $config .= "PNGTitle[{$name}]: {$name}\n";
        $config .= "ShortLegend[{$name}]: {$result->mrtgUnit}\n";
        $config .= "Options[{$name}]: {$result->mrtgOptions}\n";
        $config .= "MaxBytes[{$name}]: {$result->mrtgMaxBytes}\n";
        $config .= "kMG[{$name}]: {$result->mrtgKmg}\n";

        return $config;
    }
}
