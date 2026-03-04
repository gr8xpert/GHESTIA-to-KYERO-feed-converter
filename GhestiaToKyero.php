<?php
/**
 * Ghestia to Kyero Feed Converter
 * Converts Ghestia XML format to Kyero v3 XML format
 */

class GhestiaToKyero
{
    // Property type mapping: TipoGenerico/TipoEspecifico => Kyero type
    private $propertyTypeMap = [
        // Piso (Apartment types)
        'Piso|Piso' => 'apartment',
        'Piso|Apartamento' => 'apartment',
        'Piso|Ático' => 'penthouse',
        'Piso|Loft' => 'apartment',
        'Piso|Estudio' => 'studio',
        'Piso|Dúplex' => 'apartment',
        'Piso|Habitación' => 'apartment',
        'Piso|Buhardilla' => 'apartment',
        'Piso|Otro' => 'apartment',

        // Casa (House types)
        'Casa|Bungalow' => 'bungalow',
        'Casa|Casa rural' => 'country_house',
        'Casa|Unifamiliar adosada' => 'town_house',
        'Casa|Unifamiliar aislada' => 'villa',
        'Casa|Masia' => 'finca',
        'Casa|Chalet / Torre' => 'villa',
        'Casa|Chalet' => 'villa',
        'Casa|Torre' => 'villa',
        'Casa|Otro' => 'house',
        'Casa|Buhardilla' => 'house',

        // Local (Commercial)
        'Local|Local comercial' => 'commercial',
        'Local|Tienda' => 'commercial',
        'Local|Otro' => 'commercial',

        // Oficina (Office)
        'Oficina|Oficina' => 'commercial',
        'Oficina|Despacho' => 'commercial',
        'Oficina|Otro' => 'commercial',

        // Edificio (Building)
        'Edificio|Propiedad vertical' => 'building',
        'Edificio|Propiedad' => 'building',

        // Suelo (Land)
        'Suelo|Suelo rústico' => 'plot',
        'Suelo|Suelo urbano' => 'plot',
        'Suelo|Otro' => 'plot',
        'Suelo|Terciario' => 'plot',

        // Industrial
        'Industrial|Nave industrial' => 'commercial',
        'Industrial|Local' => 'commercial',
        'Industrial|Otro' => 'commercial',

        // Parking
        'Parking|Negocio de aparcamiento' => 'garage',
        'Parking|Plaza de aparcamiento' => 'garage',
        'Parking|Otro' => 'garage',

        // Hotel
        'Hotel|Hotel' => 'hotel',
        'Hotel|Balneario' => 'hotel',
        'Hotel|Hostal' => 'hotel',

        // Otro
        'Otro|' => 'property',
    ];

    // Feature mapping: Spanish => English
    private $featureMap = [
        'Aire acondicionado' => 'Air conditioning',
        'Ascensor' => 'Lift',
        'Terraza' => 'Terrace',
        'Balcón' => 'Balcony',
        'Parking' => 'Parking',
        'Garaje' => 'Garage',
        'Trastero' => 'Storage room',
        'Amueblado' => 'Furnished',
        'Calefacción' => 'Heating',
        'Chimenea' => 'Fireplace',
        'Jardín' => 'Garden',
        'Zona ajardinada' => 'Garden',
        'Zona comunitaria' => 'Communal area',
        'Piscina' => 'Swimming pool',
        'Piscina comunitaria' => 'Communal pool',
        'Lavadero' => 'Laundry room',
        'Cocina amueblada' => 'Fitted kitchen',
        'Armarios empotrados' => 'Built-in wardrobes',
        'Internet' => 'Internet',
        'TV' => 'Television',
        'Soleado' => 'Sunny',
        'Vistas' => 'Views',
        'Vistas al mar' => 'Sea views',
        '1ª Linea' => 'Beachfront',
        'Centro Urbano' => 'Town centre',
        'Playas' => 'Near beach',
    ];

    private $agentInfo = [];
    private $log = [];

    public function __construct($agentInfo = [])
    {
        $this->agentInfo = $agentInfo;
    }

    /**
     * Download file from FTP
     */
    public function downloadFromFTP($host, $user, $pass, $remoteFile, $localFile)
    {
        $this->log[] = "Connecting to FTP: $host";

        $conn = ftp_connect($host);
        if (!$conn) {
            throw new Exception("Could not connect to FTP server: $host");
        }

        if (!ftp_login($conn, $user, $pass)) {
            ftp_close($conn);
            throw new Exception("FTP login failed for user: $user");
        }

        // Enable passive mode
        ftp_pasv($conn, true);

        $this->log[] = "Downloading: $remoteFile";

        if (!ftp_get($conn, $localFile, $remoteFile, FTP_BINARY)) {
            ftp_close($conn);
            throw new Exception("Failed to download file: $remoteFile");
        }

        ftp_close($conn);
        $this->log[] = "Downloaded successfully to: $localFile";

        return true;
    }

    /**
     * Convert Ghestia XML to Kyero XML
     */
    public function convert($inputFile, $outputFile)
    {
        $this->log[] = "Loading Ghestia XML: $inputFile";

        // Load source XML
        $ghestia = simplexml_load_file($inputFile);
        if ($ghestia === false) {
            throw new Exception("Failed to parse Ghestia XML file");
        }

        // Create Kyero XML structure
        $kyero = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><root></root>');

        // Add Kyero version node
        $kyeroNode = $kyero->addChild('kyero');
        $kyeroNode->addChild('feed_version', '3');

        // Add agent node
        $this->addAgentNode($kyero);

        // Convert properties
        $count = 0;
        $skipped = 0;
        $sourceProperties = 0;

        foreach ($ghestia->inmueble as $inmueble) {
            try {
                $sourceProperties++;
                $converted = $this->convertPropertyMultiple($kyero, $inmueble);
                $count += $converted;
            } catch (Exception $e) {
                $ref = (string)$inmueble->Referencia;
                $this->log[] = "Warning: Skipped property $ref: " . $e->getMessage();
                $skipped++;
            }
        }

        $this->log[] = "Source properties: $sourceProperties";

        $this->log[] = "Converted: $count properties";
        if ($skipped > 0) {
            $this->log[] = "Skipped: $skipped properties";
        }

        // Save output
        $this->saveXML($kyero, $outputFile);
        $this->log[] = "Saved to: $outputFile";

        return ['converted' => $count, 'skipped' => $skipped];
    }

    /**
     * Add agent information to Kyero XML
     */
    private function addAgentNode($kyero)
    {
        $agent = $kyero->addChild('agent');
        $agent->addChild('id', $this->escapeXml($this->agentInfo['id'] ?? '1'));
        $agent->addChild('name', $this->escapeXml($this->agentInfo['name'] ?? ''));
        $agent->addChild('email', $this->escapeXml($this->agentInfo['email'] ?? ''));

        if (!empty($this->agentInfo['tel'])) {
            $agent->addChild('tel', $this->escapeXml($this->agentInfo['tel']));
        }
        if (!empty($this->agentInfo['addr'])) {
            $agent->addChild('addr1', $this->escapeXml($this->agentInfo['addr']));
        }
        if (!empty($this->agentInfo['town'])) {
            $agent->addChild('town', $this->escapeXml($this->agentInfo['town']));
        }
        if (!empty($this->agentInfo['region'])) {
            $agent->addChild('region', $this->escapeXml($this->agentInfo['region']));
        }
        if (!empty($this->agentInfo['postcode'])) {
            $agent->addChild('postcode', $this->escapeXml($this->agentInfo['postcode']));
        }
        $agent->addChild('country', 'Spain');
    }

    /**
     * Convert single property - returns array of converted properties
     * (may return multiple if property has both sale and rental prices)
     */
    public function convertPropertyMultiple($kyero, $inmueble)
    {
        $count = 0;
        $operations = $this->getAllPriceInfo($inmueble);

        foreach ($operations as $op) {
            $this->convertPropertyWithOperation($kyero, $inmueble, $op['price'], $op['price_freq'], $op['suffix']);
            $count++;
        }

        return $count;
    }

    /**
     * Get all available price operations for a property
     */
    private function getAllPriceInfo($inmueble)
    {
        $operations = [];

        $precioVenta = $this->parseFloat((string)$inmueble->PrecioVenta);
        $precioAlquiler = $this->parseFloat((string)$inmueble->PrecioAlquiler);
        $precioTemporada = $this->parseFloat((string)$inmueble->PrecioAlquilerTemporada);

        if ($precioVenta && $precioVenta > 0) {
            $operations[] = [
                'price' => (int)$precioVenta,
                'price_freq' => 'sale',
                'suffix' => '-sale'
            ];
        }

        if ($precioAlquiler && $precioAlquiler > 0) {
            $operations[] = [
                'price' => (int)$precioAlquiler,
                'price_freq' => 'month',
                'suffix' => '-rent'
            ];
        }

        if ($precioTemporada && $precioTemporada > 0) {
            $operations[] = [
                'price' => (int)$precioTemporada,
                'price_freq' => 'week',
                'suffix' => '-holiday'
            ];
        }

        // If no price found, return default
        if (empty($operations)) {
            $operations[] = [
                'price' => 0,
                'price_freq' => 'sale',
                'suffix' => ''
            ];
        }

        // If only one operation, no suffix needed
        if (count($operations) === 1) {
            $operations[0]['suffix'] = '';
        }

        return $operations;
    }

    /**
     * Convert single property with specific operation
     */
    private function convertPropertyWithOperation($kyero, $inmueble, $price, $priceFreq, $suffix)
    {
        $prop = $kyero->addChild('property');

        // Required: id, ref (with suffix for multiple operations)
        $referencia = (string)$inmueble->Referencia;
        $prop->addChild('id', $this->escapeXml($referencia . $suffix));
        $prop->addChild('ref', $this->escapeXml($referencia . $suffix));

        // Required: date
        $fecha = (string)$inmueble->FechaModificacion;
        $prop->addChild('date', $this->formatDate($fecha));

        // Required: price, currency, price_freq
        $prop->addChild('price', $price ?: '0');
        $prop->addChild('currency', 'EUR');
        $prop->addChild('price_freq', $priceFreq ?: 'sale');

        // Required: type
        $tipoGenerico = (string)$inmueble->TipoGenerico;
        $tipoEspecifico = (string)$inmueble->TipoEspecifico;
        $prop->addChild('type', $this->getKyeroType($tipoGenerico, $tipoEspecifico));

        // Required: town, province
        $prop->addChild('town', $this->escapeXml((string)$inmueble->Localidad));
        $prop->addChild('province', $this->escapeXml((string)$inmueble->Provincia));
        $prop->addChild('country', 'Spain');

        // Optional: postcode
        $postcode = (string)$inmueble->CodigoPostal;
        if (!empty($postcode)) {
            $prop->addChild('postcode', $this->escapeXml($postcode));
        }

        // Optional: location_detail (Zona)
        $zona = (string)$inmueble->Zona;
        if (!empty($zona)) {
            $prop->addChild('location_detail', $this->escapeXml($zona));
        }

        // Optional: GPS coordinates
        $lat = (string)$inmueble->Latitud;
        $lon = (string)$inmueble->Longitud;
        if (!empty($lat) && !empty($lon)) {
            $location = $prop->addChild('location');
            $location->addChild('latitude', $lat);
            $location->addChild('longitude', $lon);
        }

        // Caracteristicas
        $caracteristicas = $this->parseCaracteristicas($inmueble);

        // Beds
        if (isset($caracteristicas['Nº de dormitorios'])) {
            $beds = $this->parseInt($caracteristicas['Nº de dormitorios']);
            if ($beds !== null) {
                $prop->addChild('beds', $beds);
            }
        }

        // Baths
        if (isset($caracteristicas['Nº de baños'])) {
            $baths = $this->parseInt($caracteristicas['Nº de baños']);
            if ($baths !== null) {
                $prop->addChild('baths', $baths);
            }
        }

        // Pool
        if (isset($caracteristicas['Piscina']) && strtolower($caracteristicas['Piscina']) == 'si') {
            $prop->addChild('pool', '1');
        }

        // New build
        $obraNueva = (string)$inmueble->ObraNueva;
        if (strtolower($obraNueva) == 'si') {
            $prop->addChild('new_build', '1');
        }

        // Surface area
        $supConstruida = $this->parseFloat((string)$inmueble->SuperficieConstruida);
        $supUtil = $this->parseFloat((string)$inmueble->SuperficieUtil);
        if ($supConstruida || $supUtil) {
            $surface = $prop->addChild('surface_area');
            if ($supConstruida) {
                $surface->addChild('built', (int)$supConstruida);
            }
            if ($supUtil) {
                $surface->addChild('plot', (int)$supUtil);
            }
        }

        // Energy rating
        $consumo = $caracteristicas['Categoría del consumo energético'] ?? null;
        $emisiones = $caracteristicas['Categoría de les emisiones'] ?? null;
        if ($consumo || $emisiones) {
            $validRatings = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
            $energy = $prop->addChild('energy_rating');
            if ($consumo && in_array($consumo, $validRatings)) {
                $energy->addChild('consumption', $consumo);
            }
            if ($emisiones && in_array($emisiones, $validRatings)) {
                $energy->addChild('emissions', $emisiones);
            }
        }

        // Description
        $anuncio = trim((string)$inmueble->Anuncio);
        if (!empty($anuncio)) {
            $desc = $prop->addChild('desc');
            $desc->addChild('es', $this->escapeXml($anuncio));
        }

        // Features
        $features = $this->extractFeatures($caracteristicas);
        if (!empty($features)) {
            $featuresNode = $prop->addChild('features');
            foreach ($features as $feature) {
                $featuresNode->addChild('feature', $this->escapeXml($feature));
            }
        }

        // Images
        $this->addImages($prop, $inmueble);
    }

    /**
     * Parse Caracteristicas into associative array
     */
    private function parseCaracteristicas($inmueble)
    {
        $result = [];
        if (isset($inmueble->Caracteristicas)) {
            foreach ($inmueble->Caracteristicas->Caracteristica as $car) {
                $desc = (string)$car->Descripcion;
                $valor = (string)$car->Valor;
                if (!empty($desc)) {
                    $result[$desc] = $valor;
                }
            }
        }
        return $result;
    }

    /**
     * Map Ghestia type to Kyero type
     */
    private function getKyeroType($tipoGenerico, $tipoEspecifico)
    {
        $key = "$tipoGenerico|$tipoEspecifico";

        if (isset($this->propertyTypeMap[$key])) {
            return $this->propertyTypeMap[$key];
        }

        // Try with just TipoGenerico
        foreach ($this->propertyTypeMap as $mapKey => $kyeroType) {
            if (strpos($mapKey, "$tipoGenerico|") === 0) {
                return $kyeroType;
            }
        }

        return 'property';
    }

    /**
     * Extract features from caracteristicas
     */
    private function extractFeatures($caracteristicas)
    {
        $features = [];
        foreach ($this->featureMap as $spanish => $english) {
            if (isset($caracteristicas[$spanish]) && strtolower($caracteristicas[$spanish]) == 'si') {
                $features[] = $english;
            }
        }
        return $features;
    }

    /**
     * Add images to property
     */
    private function addImages($prop, $inmueble)
    {
        if (!isset($inmueble->Imagenes)) {
            return;
        }

        $images = $prop->addChild('images');
        $count = 0;

        foreach ($inmueble->Imagenes->foto as $foto) {
            if ($count >= 50) break; // Kyero limit

            $url = (string)$foto->url;
            if (!empty($url)) {
                $count++;
                $image = $images->addChild('image');
                $image->addAttribute('id', $count);
                $image->addChild('url', $this->escapeXml($url));
            }
        }
    }

    /**
     * Format date for Kyero
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return date('Y-m-d H:i:s');
        }
        if (strpos($date, ' ') !== false) {
            return $date;
        }
        return $date . ' 00:00:00';
    }

    /**
     * Parse integer
     */
    private function parseInt($value)
    {
        if (empty($value)) return null;
        return (int)floatval($value);
    }

    /**
     * Parse float
     */
    private function parseFloat($value)
    {
        if (empty($value)) return null;
        return floatval($value);
    }

    /**
     * Escape XML special characters
     */
    private function escapeXml($string)
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Save XML with proper formatting
     */
    private function saveXML($xml, $outputFile)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save($outputFile);
    }

    /**
     * Get log messages
     */
    public function getLog()
    {
        return $this->log;
    }
}
