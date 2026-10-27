<div class="page-head"><div><h2>Asistente de reportes</h2><p>Pregunte en lenguaje natural y obtenga tablas, gráficos y resúmenes de su pollería.</p></div>
  <div class="d-flex gap-2"><button class="btn btn-ghost" id="btnEsq"><i class="bi bi-diagram-3 me-1"></i>Qué datos ve la IA</button><button class="btn btn-ghost" id="btnCfg"><i class="bi bi-gear me-1"></i>Configurar IA</button></div></div>

<div class="ia-hero"><h3><i class="bi bi-stars me-2"></i>¿Qué quiere saber de su negocio?</h3><p>Escriba su pregunta como se la haría a un analista. Ejemplo: «¿Cuántos pollos enteros y medios vendimos este mes?»</p>
  <form class="ia-ask" id="frmAsk"><textarea id="iaQ" rows="1" maxlength="500" placeholder="Escriba su pregunta aquí…" aria-label="Pregunta"></textarea><button id="iaGo" class="ia-go"><i class="bi bi-send-fill"></i> Preguntar</button></form>
  <div class="ia-chips" id="iaChips"></div></div>

<div id="iaSetup" class="card-soft ia-setup p-4 mb-3" style="display:none"><h6 class="fw-bold"><i class="bi bi-rocket-takeoff me-2 text-brand"></i>Active el asistente en 2 minutos (gratis)</h6>
  <div class="st"><b>1</b><div>Entre a <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">aistudio.google.com/apikey</a> con su cuenta de Google y cree una clave gratuita.</div></div>
  <div class="st"><b>2</b><div class="flex-grow-1">Pegue la clave aquí y pulse <b>Activar</b>:
    <form class="d-flex gap-2 mt-2 flex-wrap" id="frmQuick"><input type="password" class="form-control" id="qKey" style="max-width:340px" placeholder="Clave de Gemini" autocomplete="new-password"><button class="btn btn-brand" id="qGo">Activar</button></form><div class="small text-danger mt-1" id="qErr"></div></div></div>
  <div class="small text-muted mb-2">También puede usar Groq, OpenRouter u Ollama (local, sin enviar datos a internet) desde la configuración avanzada.</div>
  <button class="btn btn-ghost btn-sm" onclick="document.getElementById('btnCfg').click()"><i class="bi bi-gear me-1"></i>Configuración avanzada</button></div>

<div class="row g-3"><div class="col-lg-8"><div id="iaOut"><div class="card-soft text-center text-muted py-5"><i class="bi bi-chat-square-text" style="font-size:2.4rem"></i><div class="mt-2">Aquí aparecerán sus resultados</div><div class="small">Puede hacer preguntas de seguimiento, como «ahora solo delivery».</div></div></div></div>
  <div class="col-lg-4"><div class="card-soft ia-side p-3 mb-3"><h6 class="fw-bold mb-2"><i class="bi bi-star-fill text-warning me-1"></i>Favoritas</h6><div id="iaFav"></div></div>
    <div class="card-soft ia-side p-3"><h6 class="fw-bold mb-2"><i class="bi bi-clock-history me-1"></i>Recientes</h6><div id="iaHis"></div></div></div></div>

<div class="modal fade" id="mdlCfg" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content border-0" style="border-radius:20px">
 <form id="frmCfg" novalidate><div class="modal-header border-0 pb-0 px-4 pt-4"><h5 class="modal-title fw-bold"><i class="bi bi-gear me-2"></i>Configurar el asistente de IA</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
 <div class="modal-body px-4"><div class="row g-3">
  <div class="col-12"><label class="form-label">Proveedor</label><select class="form-select" name="proveedor" id="cProv"></select><div class="form-text" id="cAyuda"></div></div>
  <div class="col-md-7"><label class="form-label">URL de la API</label><input class="form-control" name="url" id="cUrl" autocomplete="off"></div>
  <div class="col-md-5"><label class="form-label">Modelo</label><input class="form-control" name="modelo" id="cMod" autocomplete="off"></div>
  <div class="col-12"><label class="form-label">Clave (API key)</label><input type="password" class="form-control" name="clave" id="cKey" autocomplete="new-password"><div class="form-text" id="cKeyH"></div></div>
  <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="resumen" id="cRes"><label class="form-check-label" for="cRes">Generar un resumen en lenguaje natural de cada resultado (usa una segunda consulta a la IA)</label></div></div>
  <div class="col-12"><div class="alert alert-light border small mb-0"><i class="bi bi-shield-lock me-1"></i><b>Privacidad y seguridad.</b> La IA solo ve la estructura de unas vistas de lectura (sin contraseñas ni usuarios del sistema) y su consulta se valida antes de ejecutarse: solo SELECT, en modo lectura, con límite de tiempo y de filas. Al proveedor se envía su pregunta y, si activa el resumen, hasta 25 filas del resultado (pueden incluir nombres de platos o clientes). Con <b>Ollama</b> nada sale de su PC. La clave se guarda cifrada. Estas herramientas solo las ve el administrador.</div></div>
  <div class="col-12 text-danger small" id="cErr"></div></div></div>
 <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost me-auto" id="cTest"><i class="bi bi-plug me-1"></i>Probar conexión</button><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button><button class="btn btn-brand" id="cSave"><i class="bi bi-check2 me-1"></i>Guardar</button></div></form></div></div></div>

<div class="modal fade" id="mdlEsq" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable"><div class="modal-content border-0" style="border-radius:20px">
 <div class="modal-header border-0 px-4 pt-4"><h5 class="modal-title fw-bold"><i class="bi bi-diagram-3 me-2"></i>Datos que ve la IA</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
 <div class="modal-body px-4"><p class="small text-muted">Solo estas vistas de lectura. Nunca accede a las tablas reales, ni a usuarios o contraseñas.</p><pre class="ia-sql" id="esqTxt" style="margin:0"></pre></div></div></div></div>
