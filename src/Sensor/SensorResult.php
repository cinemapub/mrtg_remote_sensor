<?php

declare(strict_types=1);

namespace MrtgSensor\Sensor;

final readonly class SensorResult
{
    public function __construct(
        public float|int|string $value1,
        public float|int|string $value2,
        public string $uptime,
        public string $server,
        public string $name1,
        public string $name2,
        public string $description,
        public string $mrtgUnit,
        public string $mrtgOptions,
        public int|float $mrtgMaxBytes,
        public string $mrtgKmg,
        public string $url,
        public string $cfgUrl,
        public string $mrtgName,
        public string $version,
    ) {}

    public function toArray(): array
    {
        return [
            'value1' => $this->value1,
            'value2' => $this->value2,
            'uptime' => $this->uptime,
            'server' => $this->server,
            'name1' => $this->name1,
            'name2' => $this->name2,
            'description' => $this->description,
            'mrtg_unit' => $this->mrtgUnit,
            'mrtg_options' => $this->mrtgOptions,
            'mrtg_maxbytes' => $this->mrtgMaxBytes,
            'mrtg_kmg' => $this->mrtgKmg,
            'url' => $this->url,
            'cfgurl' => $this->cfgUrl,
            'mrtg_name' => $this->mrtgName,
            'version' => $this->version,
        ];
    }
}
