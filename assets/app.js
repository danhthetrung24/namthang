const MAX_MEDIA_FILES = 10;
const MAX_VIDEO_BYTES = 100 * 1024 * 1024;
const state = { media: [], submitting: false };
const $ = (id) => document.getElementById(id);

document.addEventListener('DOMContentLoaded', () => {
  bindEvents();
  bindVehicleVideos();
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
  $('checkerImages')?.addEventListener('change', handleMediaSelect);
  $('magicTicketForm')?.addEventListener('submit', submitForm);
}

function bindVehicleVideos() {
  document.querySelectorAll('.vehicle-video-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const src = btn.dataset.videoSrc;
      if (!src) return;

      if (btn.dataset.videoKind === 'file') {
        const video = document.createElement('video');
        video.src = src;
        video.title = btn.dataset.videoTitle || 'Vehicle video';
        video.controls = true;
        video.autoplay = true;
        video.playsInline = true;
        video.preload = 'metadata';
        btn.replaceWith(video);
        video.play().catch(() => {});
        return;
      }

      const iframe = document.createElement('iframe');
      iframe.src = src;
      iframe.title = btn.dataset.videoTitle || 'Vehicle video';
      iframe.loading = 'lazy';
      iframe.allow = 'autoplay; fullscreen';
      iframe.allowFullscreen = true;
      btn.replaceWith(iframe);
    });
  });
}

function scrollToForm() {
  const formCard = $('magicTicketFormCard');
  if (!formCard) return;
  const headerHeight = document.querySelector('.header')?.getBoundingClientRect().height || 0;
  const y = formCard.getBoundingClientRect().top + window.pageYOffset - Math.max(headerHeight + 18, 96);
  window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
}

async function handleMediaSelect(event) {
  const files = Array.from(event.target.files || []);
  const available = MAX_MEDIA_FILES - state.media.length;
  if (available <= 0) {
    showToast('Chỉ cho phép tối đa 10 file.', true);
    event.target.value = '';
    return;
  }
  if (files.length > available) showToast('Chỉ lấy thêm ' + available + ' file.', true);

  for (const file of files.slice(0, available)) {
    if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) continue;
    try {
      if (file.type.startsWith('image/')) {
        state.media.push(await compressImage(file));
      } else {
        if (file.size > MAX_VIDEO_BYTES) {
          showToast('Video "' + file.name + '" vượt quá 100MB.', true);
          continue;
        }
        state.media.push({
          kind: 'video',
          file,
          name: file.name || 'checkin-video.mp4',
          mimeType: file.type || 'video/mp4',
          preview: URL.createObjectURL(file),
        });
      }
    } catch (_) {
      showToast('Có file không thể xử lý. Vui lòng thử file khác.', true);
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
        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Cannot compress image'));
            return;
          }
          const name = (file.name || 'checkin').replace(/\.[^/.]+$/, '') + '.jpg';
          const compressedFile = new File([blob], name, { type: 'image/jpeg' });
          resolve({
            kind: 'image',
            file: compressedFile,
            name,
            mimeType: 'image/jpeg',
            preview: URL.createObjectURL(compressedFile),
          });
        }, 'image/jpeg', 0.82);
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
  state.media.forEach((media, index) => {
    const previewItem = document.createElement('div');
    previewItem.className = 'preview-item';
    previewItem.innerHTML = media.kind === 'video'
      ? `<video src="${media.preview}" muted playsinline preload="metadata" aria-label="Video ${index + 1}"></video><span class="video-badge">Video</span><button class="remove-btn" type="button">×</button>`
      : `<img src="${media.preview}" alt="Ảnh ${index + 1}"><button class="remove-btn" type="button">×</button>`;
    previewItem.querySelector('button').addEventListener('click', () => {
      URL.revokeObjectURL(media.preview);
      state.media.splice(index, 1);
      renderPreview();
    });
    grid.appendChild(previewItem);
  });
  const count = $('previewCount');
  if (count) count.textContent = state.media.length ? `Đã chọn ${state.media.length} file.` : 'Chưa chọn file nào.';
}

async function submitForm(event) {
  event.preventDefault();
  if (state.submitting) return;

  const soDienThoai = $('soDienThoai')?.value.trim() || '';
  const hoTen = $('hoTen')?.value.trim() || '';
  const linkCheckIn = $('linkCheckIn')?.value.trim() || '';

  if (!soDienThoai) return focusError('soDienThoai', 'Vui lòng nhập số điện thoại.');
  if (!hoTen) return focusError('hoTen', 'Vui lòng nhập họ tên.');
  if (!linkCheckIn && state.media.length === 0) return focusError('linkCheckIn', 'Vui lòng dán link hoặc tải ảnh/video checkin.');

  const payload = new FormData();
  payload.append('soDienThoai', soDienThoai);
  payload.append('hoTen', hoTen);
  payload.append('linkCheckIn', linkCheckIn);
  payload.append('platform', detectPlatform());
  payload.append('userAgent', navigator.userAgent || '');
  state.media.forEach(({ file }) => payload.append('media[]', file, file.name));

  state.submitting = true;
  setSubmitLoading(true);
  try {
    const res = await fetch('submit.php', {
      method: 'POST',
      body: payload,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) throw new Error(data.message || 'Không thể lưu dữ liệu.');

    $('magicTicketForm')?.reset();
    clearMedia();
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

function clearMedia() {
  state.media.forEach(({ preview }) => URL.revokeObjectURL(preview));
  state.media = [];
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
