window.addEventListener("DOMContentLoaded", function () {
  const editor = document.querySelector("[data-profile-editor]");
  const openButtons = document.querySelectorAll("[data-profile-open]");
  const closeButtons = document.querySelectorAll("[data-profile-close]");
  const form = document.querySelector("[data-profile-form]");
  const bioInput = document.querySelector("[data-bio-input]");
  const bioCount = document.querySelector("[data-bio-count]");
  const usernameInput = document.querySelector("[data-username-input]");
  const avatarInput = document.querySelector("[data-avatar-input]");
  const avatarPreview = document.querySelector("[data-avatar-preview]");
  const avatarCropPanel = document.querySelector("[data-avatar-crop-panel]");
  const avatarCanvas = document.querySelector("[data-avatar-canvas]");
  const avatarZoom = document.querySelector("[data-avatar-zoom]");
  const avatarReset = document.querySelector("[data-avatar-reset]");
  const avatarApply = document.querySelector("[data-avatar-apply]");
  const removeAvatar = document.querySelector("[data-remove-avatar]");
  const maxCharacters = bioInput ? bioInput.getAttribute("maxlength") : "500";
  let sourceImage = null;
  let cropZoom = 1;
  let objectUrl = null;

  function showProfileToast(message) {
    let toast = document.querySelector(".toast:not([data-auto-toast])");
    if (!toast) {
      toast = document.createElement("div");
      toast.className = "toast";
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add("show");
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.classList.remove("show");
    }, 3500);
  }

  function setEditorOpen(open) {
    if (!editor) return;
    editor.hidden = !open;
    document.body.classList.toggle("profile-editor-open", open);
    if (open) {
      const firstField = editor.querySelector("input:not([type=file]), textarea, button");
      if (firstField) firstField.focus();
    }
  }

  openButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      setEditorOpen(true);
    });
  });

  closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      setEditorOpen(false);
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && editor && !editor.hidden) {
      setEditorOpen(false);
    }
  });

  function updateBioCount() {
    if (!bioInput || !bioCount) return;
    bioCount.textContent = bioInput.value.length + "/" + maxCharacters;
  }

  if (bioInput) {
    bioInput.addEventListener("input", updateBioCount);
    updateBioCount();
  }

  if (usernameInput) {
    usernameInput.addEventListener("input", function () {
      usernameInput.setCustomValidity(
        /^[A-Za-z0-9_]{3,20}$/.test(usernameInput.value.trim())
          ? ""
          : "Use 3-20 letters, numbers, or underscores."
      );
    });
  }

  function drawCrop() {
    if (!sourceImage || !avatarCanvas) return;
    const context = avatarCanvas.getContext("2d");
    const size = avatarCanvas.width;
    const baseScale = Math.max(size / sourceImage.naturalWidth, size / sourceImage.naturalHeight);
    const scale = baseScale * cropZoom;
    const width = sourceImage.naturalWidth * scale;
    const height = sourceImage.naturalHeight * scale;
    context.clearRect(0, 0, size, size);
    context.drawImage(sourceImage, (size - width) / 2, (size - height) / 2, width, height);
  }

  function resetCrop() {
    cropZoom = 1;
    if (avatarZoom) avatarZoom.value = "1";
    drawCrop();
  }

  function applyCrop() {
    if (!avatarCanvas || !avatarInput) return;
    if (typeof avatarCanvas.toBlob !== "function") {
      showProfileToast("This browser cannot create the cropped image.");
      return;
    }
    avatarCanvas.toBlob(function (blob) {
      if (!blob) {
        showProfileToast("The cropped image could not be created.");
        return;
      }
      const croppedFile = new File([blob], "profile-crop.png", { type: "image/png" });
      if (typeof DataTransfer === "function") {
        const transfer = new DataTransfer();
        transfer.items.add(croppedFile);
        avatarInput.files = transfer.files;
      } else {
        showProfileToast("Crop applied for preview; choose the image again to save it.");
      }
      if (avatarCropPanel) avatarCropPanel.hidden = true;
      showProfileToast("Crop applied. Save your profile when you are ready.");
    }, "image/png", 0.92);
  }

  if (avatarInput) {
    avatarInput.addEventListener("change", function () {
      const file = avatarInput.files && avatarInput.files[0];
      if (!file) return;
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
        avatarInput.value = "";
        showProfileToast("Choose a JPG, PNG, or WebP image.");
        return;
      }
      if (file.size > 5242880) {
        avatarInput.value = "";
        showProfileToast("Images must be smaller than 5MB.");
        return;
      }
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = URL.createObjectURL(file);
      sourceImage = new Image();
      sourceImage.onload = function () {
        if (avatarPreview) avatarPreview.src = objectUrl;
        if (avatarCropPanel) avatarCropPanel.hidden = false;
        resetCrop();
      };
      sourceImage.onerror = function () {
        avatarInput.value = "";
        showProfileToast("The selected image could not be read.");
      };
      sourceImage.src = objectUrl;
    });
  }

  if (avatarZoom) avatarZoom.addEventListener("input", function () {
    cropZoom = Number(avatarZoom.value) || 1;
    drawCrop();
  });
  if (avatarReset) avatarReset.addEventListener("click", resetCrop);
  if (avatarApply) avatarApply.addEventListener("click", applyCrop);

  if (removeAvatar) {
    removeAvatar.addEventListener("change", function () {
      if (!avatarInput) return;
      avatarInput.disabled = removeAvatar.checked;
      if (removeAvatar.checked) {
        if (avatarCropPanel) avatarCropPanel.hidden = true;
        sourceImage = null;
      }
    });
  }

  const cancelPending = document.querySelector("[data-cancel-pending-email]");
  const emailField = document.getElementById("profile-email");
  if (cancelPending && emailField) {
    cancelPending.addEventListener("change", function () {
      if (cancelPending.checked) {
        emailField.value = emailField.defaultValue;
        emailField.disabled = true;
      } else {
        emailField.disabled = false;
      }
    });
  }

  if (form) {
    form.addEventListener("submit", function (event) {
      if (avatarCropPanel && !avatarCropPanel.hidden) {
        event.preventDefault();
        showProfileToast("Apply your crop before saving.");
      }
    });
  }

  document.querySelectorAll("[data-auto-toast]").forEach(function (toast) {
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.classList.remove("show");
    }, 4500);
  });
});
