'use strict';
(() => {
  const $ = (selector) => document.querySelector(selector);
  const safe = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
  $('#menuToggle')?.addEventListener('click', () => $('#appShell')?.classList.toggle('show-sidebar'));
  document.querySelectorAll('[data-confirm]').forEach((form) => form.addEventListener('submit', (e) => { if (!confirm(form.dataset.confirm)) e.preventDefault(); }));
  document.querySelectorAll('.alert-close').forEach((btn) => btn.addEventListener('click', () => btn.closest('.alert')?.remove()));
  document.querySelectorAll('[data-toggle-password]').forEach((btn) => btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.togglePassword); if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password'; btn.textContent = input.type === 'password' ? 'Hiện' : 'Ẩn';
  }));

  const form = $('[data-recommender]');
  if (!form) return;
  const teacher = $('#teacher'), branch = $('#branch'), day = $('#day'), start = $('#start'), end = $('#end'), subject = $('#subject');
  const recommendations = $('#recommendations'), availability = $('#availability');
  let requestSequence = 0, debounceTimer;
  const selected = () => ({teacher: teacher.value, branch: branch.value, day: day.value, start: start.value, end: end.value, subject: subject.value.trim(), skip: window.scheduleEditId || 0});
  const urlParams = (params) => new URLSearchParams(params).toString();
  const refresh = async () => {
    const data = selected(), sequence = ++requestSequence;
    if (+data.start > +data.end) {
      recommendations.innerHTML = '<div class="empty-advice">Tiết kết thúc phải lớn hơn hoặc bằng tiết bắt đầu.</div>';
      availability.innerHTML = '<div class="empty-advice">Chưa chọn khoảng tiết hợp lệ.</div>'; return;
    }
    if (!data.branch || !data.subject) {
      recommendations.innerHTML = '<div class="empty-advice">Nhập tên học phần và chọn cơ sở để xem gợi ý.</div>';
    } else {
      recommendations.innerHTML = '<div class="empty-advice">Đang kiểm tra lịch và chuyên môn...</div>';
      try {
        const response = await fetch('api/suggestions.php?' + urlParams(data), {credentials: 'same-origin'});
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const result = await response.json();
        if (sequence !== requestSequence) return;
        recommendations.innerHTML = result.recommendations.length ? result.recommendations.map((item) =>
          `<button type="button" class="recommend-item" data-teacher="${item.id}"><span class="avatar-inline">${safe(Array.from(item.name).slice(-1)[0])}</span><span class="recommend-detail"><strong>${safe(item.name)}</strong><small>${safe(item.expertise)}</small></span><span class="recommend-score">${item.score}đ</span></button>`
        ).join('') : `<div class="empty-advice">${safe(result.message)}</div>`;
        recommendations.querySelectorAll('[data-teacher]').forEach((btn) => btn.addEventListener('click', () => { teacher.value = btn.dataset.teacher; refresh(); }));
      } catch (error) { if (sequence === requestSequence) recommendations.innerHTML = '<div class="empty-advice">Không thể tải gợi ý. Vui lòng kiểm tra kết nối.</div>'; }
    }
    if (!data.teacher || !data.branch) { availability.innerHTML = '<div class="empty-advice">Chọn giảng viên và cơ sở để xem các tiết trống.</div>'; return; }
    try {
      const response = await fetch('api/availability.php?' + urlParams(data), {credentials: 'same-origin'});
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const result = await response.json(); if (sequence !== requestSequence) return;
      availability.innerHTML = result.periods.map((item) => `<span class="slot ${item.available?'free':'busy'}" title="${safe(item.description || 'Có thể phân lịch')}">T${item.period}</span>`).join('') || '<div class="empty-advice">Chưa có dữ liệu.</div>';
    } catch (error) { if (sequence === requestSequence) availability.innerHTML = '<div class="empty-advice">Không tải được dữ liệu tiết trống.</div>'; }
  };
  [teacher, branch, day, start, end].forEach((field) => field?.addEventListener('change', refresh));
  subject?.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(refresh, 320); });
  form.addEventListener('submit', (event) => {
    if (+start.value > +end.value) { event.preventDefault(); alert('Tiết kết thúc phải lớn hơn hoặc bằng tiết bắt đầu.'); end.focus(); }
  });
  refresh();
})();
