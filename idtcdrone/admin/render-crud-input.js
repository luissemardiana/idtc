const renderCrudInput = (fieldDef, value = '') => {
  const [name, label, type] = fieldDef;
  const safeVal = escapeHtml(value);
  if (currentModule === 'gallery' && name === 'category') return galleryCategoryField(value);

  if (type === 'textarea') {
    return `<label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label><textarea name="${escapeHtml(name)}" id="crud-${escapeHtml(name)}-input" rows="3" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm">${safeVal}</textarea>`;
  }
  if (type === 'number') {
    return `<label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label><input type="number" name="${escapeHtml(name)}" id="crud-${escapeHtml(name)}-input" value="${safeVal}" min="0" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm">`;
  }
  if (type === 'select') {
    let optionsHtml = '';
    if (name === 'status') {
      optionsHtml = '<option value="active">Active</option><option value="draft">Draft</option>';
    } else {
      optionsHtml = '<option value="">Pilih…</option>';
    }
    return `<label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label><select name="${escapeHtml(name)}" id="crud-${escapeHtml(name)}-input" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm">${optionsHtml}</select>`;
  }

  if (name === 'image_url' && currentModule === 'certifications') {
    return `
      <div class="rounded-xl border border-dashed border-[#D8C9A0] bg-[#FFFCF7] p-3">
        <label class="block text-xs font-bold text-[#111111]">Unggah foto sertifikasi dari browser/perangkat</label>
        <input type="file" accept="image/jpeg,image/png,image/webp" name="cert_image_file" class="mt-1.5 block w-full text-xs">
        <p class="mt-1 text-[11px] text-slate-500">JPG, PNG, atau WEBP, maks 5 MB.</p>
        <p id="cert-upload-message" class="mt-1 text-xs"></p>
      </div>
      <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mt-3 mb-1">Atau URL gambar (opsional)</label>
      <input type="url" name="image_url" id="crud-image_url-input" value="${safeVal}" placeholder="https://…" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm">
    `;
  }

  return `<label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label><input type="text" name="${escapeHtml(name)}" id="crud-${escapeHtml(name)}-input" value="${safeVal}" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm">`;
};
