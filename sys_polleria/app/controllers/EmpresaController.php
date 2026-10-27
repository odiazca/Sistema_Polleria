<?php
class EmpresaController extends Controller
{
    protected string $module = 'empresa';

    private const TEXT_KEYS = [
        'empresa_nombre', 'empresa_razon_social', 'empresa_ruc', 'empresa_direccion', 'empresa_ciudad', 'empresa_telefono',
        'empresa_celular', 'empresa_email', 'empresa_web', 'empresa_eslogan',
        'moneda_codigo', 'moneda_simbolo', 'moneda_nombre',
        'serie_ticket', 'serie_boleta', 'serie_factura', 'ticket_mensaje',
    ];

    public function indexAction(): void
    {
        $this->view('empresa/index', ['title' => 'Configuración de la empresa', 's' => Setting::all(), 'scripts' => ['js/empresa.js']]);
    }

    public function guardarAction(): void
    {
        try {
            $d = [];
            foreach (self::TEXT_KEYS as $k) {
                if (isset($_POST[$k])) $d[$k] = trim((string)$_POST[$k]);
            }
            if (($d['empresa_nombre'] ?? '') === '') throw new RuntimeException('El nombre de la empresa es obligatorio.');
            if (($d['moneda_simbolo'] ?? '') === '') throw new RuntimeException('Indique el símbolo de la moneda.');

            $d['moneda_posicion'] = ($_POST['moneda_posicion'] ?? 'antes') === 'despues' ? 'despues' : 'antes';
            $d['decimales'] = (string)max(0, min(4, (int)($_POST['decimales'] ?? 2)));
            $sepD = (string)($_POST['sep_decimal'] ?? '.');
            $sepM = (string)($_POST['sep_miles'] ?? ',');
            if (!in_array($sepD, ['.', ','], true)) $sepD = '.';
            if (!in_array($sepM, [',', '.', ' ', ''], true)) $sepM = ',';
            if ($sepD === $sepM) throw new RuntimeException('El separador decimal y el de miles no pueden ser iguales.');
            $d['sep_decimal'] = $sepD;
            $d['sep_miles'] = $sepM;

            $d['igv_activo'] = isset($_POST['igv_activo']) ? '1' : '0';
            $d['igv_incluido'] = isset($_POST['igv_incluido']) ? '1' : '0';
            $d['igv_porcentaje'] = (string)max(0, min(100, (float)str_replace(',', '.', $_POST['igv_porcentaje'] ?? '18')));
            $d['costo_delivery'] = (string)max(0, (float)str_replace(',', '.', $_POST['costo_delivery'] ?? '0'));
            $d['ticket_ancho'] = ($_POST['ticket_ancho'] ?? '80') === '58' ? '58' : '80';

            $tz = (string)($_POST['zona_horaria'] ?? 'America/Lima');
            if (!in_array($tz, timezone_identifiers_list(), true)) $tz = 'America/Lima';
            $d['zona_horaria'] = $tz;
            $fmt = (string)($_POST['formato_fecha'] ?? 'd/m/Y');
            $d['formato_fecha'] = in_array($fmt, ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'], true) ? $fmt : 'd/m/Y';
            $col = (string)($_POST['color_principal'] ?? '#F97316');
            $d['color_principal'] = preg_match('/^#[0-9a-fA-F]{6}$/', $col) ? $col : '#F97316';
            $d['backup_retencion'] = (string)max(3, min(100, (int)($_POST['backup_retencion'] ?? 10)));

            // Logo
            if (!empty($_POST['quitar_logo'])) {
                delete_upload(cfg('empresa_logo'));
                $d['empresa_logo'] = '';
            }
            $logo = save_image($_FILES['logo'] ?? [], 'logo', 2);
            if ($logo) {
                delete_upload(cfg('empresa_logo'));
                $d['empresa_logo'] = $logo;
            }

            Setting::setMany($d);
            Log::add('config', 'Configuración de empresa actualizada');
            flash('success', 'Configuración guardada correctamente.');
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        }
        $tab = preg_match('/^[a-z]+$/', $_POST['tab'] ?? '') ? $_POST['tab'] : 'datos';
        header('Location: ' . url('empresa/index') . '#' . $tab);
        exit;
    }
}
