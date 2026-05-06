const state = { images: [], submitting: false };
const $ = (id) => document.getElementById(id);

document.addEventListener('DOMContentLoaded', () => {
  bindEvents();
  setTimeout(() => {
    $('promoModal')?.classList.add('show');
  }, 800);
});

function bindEvents() {
  document.querySelectorAll('[data-scroll-form]').forEach((btn) => btn.addEventListener('click', () => {
    $('promoModal')?.classList.remove('show');
    scrollToForm();
  }));

  $('joinNowBtn')?.addEventListener('click', () => {
    $('promoModal')?.classList.remove('show');
    scrollToForm();
  });

  $('viewRulesBtn')?.addEventListener('click', () => {
    $('promoModal')?.classList.remove('show');
    document.getElementById('magic-ticket')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  $('closePromoBtn')?.addEventListener('click', () => $('promoModal')?.classList.remove('show'));
  $('promoModal')?.addEventListener('click', (e) => {
    if (e.target === $('promoModal')) $('promoModal')?.classList.remove('show');
  });

  document.querySelectorAll('[data-close-app]').forEach((btn) => btn.addEventListener('click', () => $('appPopup')?.classList.remove('show')));
  $('appPopup')?.addEventListener('click', (e) => {
    if (e.target === $('appPopup')) $('appPopup')?.classList.remove('show');
  });

  $('pickImagesBtn')?.addEventListener('click', () => $('checkerImages')?.click());
  $('checkerImages')?.addEventListener('change', handleImageSelect);
  $('magicTicketForm')?.addEventListener('submit', submitForm);
}

function scrollToForm() {
  const formCard = $('magicTicketFormCard');
  if (!formCard) return;
  const headerHeight = document.querySelector('.header')?.getBoundingClientRect().height || 0;
  const y = formCard.getBoundingClientRect().top + window.pageYOffset - Math.max(headerHeight + 18, 96);
  window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
}

async function handleImageSelect(event) {
  const files = Array.from(event.target.files || []);
  const available = 10 - state.images.length;
  if (available <= 0) {
    showToast('Chỉ cho phép tối đa 10 ảnh.', true);
    event.target.value = '';
    return;
  }
  if (files.length > available) showToast('Chỉ lấy thêm ' + available + ' ảnh.', true);

  for (const file of files.slice(0, available)) {
    if (!file.type.startsWith('image/')) continue;
    try {
      state.images.push(await compressImage(file));
    } catch (_) {
      showToast('Có ảnh không thể xử lý. Vui lòng thử ảnh khác.', true);
    }
  }
  renderPreview();
  event.target.value = '';
}

function compressImage(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        const maxSize = 1400;
        let { width, height } = img;
        if (width > height && width > maxSize) {
          height = Math.round((height * maxSize) / width);
          width = maxSize;
        } else if (height >= width && height > maxSize) {
          width = Math.round((width * maxSize) / height);
          height = maxSize;
        }
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.82);
        resolve({
          name: (file.name || 'checkin').replace(/\.[^/.]+$/, '') + '.jpg',
          mimeType: 'image/jpeg',
          base64: dataUrl.split(',')[1],
          preview: dataUrl,
        });
      };
      img.onerror = reject;
      img.src = e.target.result;
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

function renderPreview() {
  const grid = $('previewGrid');
  if (!grid) return;
  grid.innerHTML = '';
  state.images.forEach((img, index) => {
    const item = document.createElement('div');
    item.className = 'preview-item';
    item.innerHTML = `<img src="${img.preview}" alt="Ảnh ${index + 1}"><button class="remove-btn" type="button">×</button>`;
    item.querySelector('button').addEventListener('click', () => {
      state.images.splice(index, 1);
      renderPreview();
    });
    grid.appendChild(item);
  });
  const count = $('previewCount');
  if (count) count.textContent = state.images.length ? `Đã chọn ${state.images.length} ảnh.` : 'Chưa chọn ảnh nào.';
}

async function submitForm(event) {
  event.preventDefault();
  if (state.submitting) return;

  const payload = {
    soDienThoai: $('soDienThoai')?.value.trim() || '',
    hoTen: $('hoTen')?.value.trim() || '',
    linkCheckIn: $('linkCheckIn')?.value.trim() || '',
    platform: detectPlatform(),
    userAgent: navigator.userAgent || '',
    images: state.images.map(({ name, mimeType, base64 }) => ({ name, mimeType, base64 })),
  };

  if (!payload.soDienThoai) return focusError('soDienThoai', 'Vui lòng nhập số điện thoại.');
  if (!payload.hoTen) return focusError('hoTen', 'Vui lòng nhập họ tên.');
  if (!payload.linkCheckIn) return focusError('linkCheckIn', 'Vui lòng dán link check-in.');

  state.submitting = true;
  setSubmitLoading(true);
  try {
    const res = await fetch('submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) throw new Error(data.message || 'Không thể lưu dữ liệu.');

    $('magicTicketForm')?.reset();
    state.images = [];
    renderPreview();
    showToast('Đăng ký thành công. Mã: ' + (data.id || ''));
    setTimeout(showAppPopup, 1200);
  } catch (err) {
    showToast(err.message || 'Có lỗi xảy ra khi gửi dữ liệu.', true);
  } finally {
    state.submitting = false;
    setSubmitLoading(false);
  }
}

function focusError(id, message) {
  showToast(message, true);
  $(id)?.focus();
}

function setSubmitLoading(loading) {
  const btn = $('submitBtn');
  if (!btn) return;
  btn.disabled = loading;
  btn.textContent = loading ? 'ĐANG GỬI...' : 'NHẬN MAGIC TICKET';
}

function detectPlatform() {
  const ua = navigator.userAgent || '';
  if (/Android/i.test(ua)) return 'Android';
  if (/iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) return 'iOS';
  return 'Desktop/Khác';
}

function detectMobilePlatform() {
  const ua = navigator.userAgent || '';
  if (/Android/i.test(ua)) return 'android';
  if (/iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) return 'ios';
  return 'other';
}

function showAppPopup() {
  const platform = detectMobilePlatform();
  const btn = $('appPopupDownloadBtn');
  const text = $('appPopupText');
  const cfg = window.APP_CONFIG || {};
  if (!btn || !text) return;

  if (platform === 'android') {
    btn.href = cfg.androidLink || '#';
    text.textContent = 'Bạn đang dùng Android. Nhấn nút bên dưới để tải ứng dụng Taxi Nam Thắng trên Google Play.';
  } else if (platform === 'ios') {
    btn.href = cfg.iosLink || '#';
    text.textContent = 'Bạn đang dùng iPhone/iPad. Nhấn nút bên dưới để tải ứng dụng Taxi Nam Thắng trên App Store.';
  } else {
    btn.href = cfg.androidLink || '#';
    text.textContent = 'Nhấn nút bên dưới để mở liên kết tải ứng dụng Taxi Nam Thắng.';
  }
  $('appPopup')?.classList.add('show');
}

function showToast(message, isError = false) {
  const toast = $('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.className = 'toast show' + (isError ? ' error' : '');
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => {
    toast.className = 'toast' + (isError ? ' error' : '');
  }, 2800);
}
