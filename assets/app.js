'use strict';

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const field = document.getElementById(button.dataset.togglePassword);
      if (!field) return;
      const show = field.type === 'password';
      field.type = show ? 'text' : 'password';
      button.textContent = show ? 'Ocultar' : 'Ver';
      button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });

  const inspection = document.getElementById('fecha-inspeccion');
  const expiration = document.getElementById('fecha-vencimiento');
  const suggestButton = document.getElementById('suggest-expiration');
  if (inspection && expiration && suggestButton) {
    suggestButton.addEventListener('click', () => {
      if (!inspection.value) {
        inspection.focus();
        inspection.setCustomValidity('Primero selecciona la fecha de inspección.');
        inspection.reportValidity();
        inspection.addEventListener('change', () => inspection.setCustomValidity(''), { once: true });
        return;
      }
      const date = new Date(`${inspection.value}T12:00:00`);
      date.setFullYear(date.getFullYear() + 1);
      // Corrige fechas tipo 29 de febrero para no sugerir una fecha inexistente.
      if (date.getMonth() !== new Date(`${inspection.value}T12:00:00`).getMonth()) date.setDate(0);
      expiration.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
      expiration.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  document.querySelectorAll('input[name="placa"]').forEach((field) => {
    field.addEventListener('input', () => {
      const cursor = field.selectionStart;
      field.value = field.value.toUpperCase().replace(/\s+/g, '');
      try { field.setSelectionRange(cursor, cursor); } catch (_) {}
    });
  });
});
