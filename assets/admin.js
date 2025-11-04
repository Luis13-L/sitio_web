// Tooltips (Bootstrap)
(function(){
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>{
      new bootstrap.Tooltip(el);
    });
  }
})();

// Confirmación de borrado (SweetAlert)
function attachDeleteConfirms(selector = 'form.js-delete'){
  document.querySelectorAll(selector).forEach(form=>{
    form.addEventListener('submit', (e)=>{
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: '¿Eliminar?',
        text: 'Se eliminarán también los archivos asociados.',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
      }).then(res=>{
        if(res.isConfirmed){
          Swal.fire({title:'Eliminando…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading()});
          form.submit();
        }
      });
    }, {passive:false});
  });
}

// Confirmación de guardado (para Editar/Crear)
function attachSaveConfirm(formSelector = 'form.js-save'){
  const form = document.querySelector(formSelector);
  if(!form) return;

  form.addEventListener('submit', (e)=>{
    if(!form.checkValidity()) return; // que HTML5 valide
    e.preventDefault();

    const borrarPdf = document.getElementById('eliminar_archivo')?.checked;
    const msg = borrarPdf
      ? 'También se eliminará el PDF actual. ¿Deseas continuar?'
      : '¿Estás seguro que quieres guardar los cambios?';

    Swal.fire({
      icon: 'question',
      title: 'Guardar cambios',
      text: msg,
      showCancelButton: true,
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d'
    }).then(res=>{
      if(res.isConfirmed){
        Swal.fire({title:'Guardando…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading()});
        form.submit();
      }
    });
  }, {passive:false});
}

// Exportar a global para usar desde las vistas
window.AdminUX = { attachDeleteConfirms, attachSaveConfirm };
