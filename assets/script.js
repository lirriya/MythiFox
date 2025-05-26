window.addEventListener("DOMContentLoaded", () => {
  // Show login error alerts
  if (typeof loginError !== "undefined") {
    switch (loginError) {
      case "username":
        alert("Username not found. Please check your spelling or sign up first.");
        break;
      case "password":
        alert("Incorrect password. Try again or reset your password.");
        break;
      case "usertaken":
        alert("This username is already taken. Please choose another.");
        break;
    }
  }

  // Popup toggle for login/signup
  const wrapper = document.querySelector('.wrapper');
  const btnPopup = document.querySelector('.btnLogin-popup');
  const closeBtn = document.querySelector('.close');
  const downButton = document.getElementById('arrow');
  const dropdownMenu = document.querySelector('.navigation .dropdown');

  if (btnPopup) {
    btnPopup.addEventListener('click', () => {
      wrapper.classList.add('active-popup');
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      wrapper.classList.remove('active-popup');
    });
  }

  if (downButton && dropdownMenu) {
    downButton.addEventListener('click', () => {
      dropdownMenu.classList.toggle('show');
    });
  }

  // Profile form toggle
  const toggleBtn = document.getElementById("toggleEditBtn");
  const form = document.getElementById("epf-form");

  if (toggleBtn && form) {
    toggleBtn.addEventListener("click", () => {
      const isVisible = form.classList.contains("epf-hidden");
      form.classList.toggle("epf-hidden");
      toggleBtn.textContent = isVisible ? "Close" : "Edit Profile";
    });
  }

  // Handle in-place editing
  const editButtons = document.querySelectorAll('.edit-btn');
  editButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const postId = btn.dataset.postId;
      const contentDiv = document.getElementById('content-' + postId);
      const text = contentDiv.innerText.trim();
      contentDiv.innerHTML = `<textarea name="content" rows="4" style="width:100%;">${text}</textarea>`;

      // Show Save button
      const saveBtn = document.querySelector(`.save-btn[data-post-id="${postId}"]`);
      if (saveBtn) saveBtn.style.display = 'inline';

      // Show the update form
      const form = document.getElementById('form-' + postId);
      if (form) form.style.display = 'block';
    });
  });

  // Confirm delete with a dialog
  const deleteForms = document.querySelectorAll('.delete-form');
  deleteForms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const confirmDelete = confirm('Are you sure you want to delete this post?');
      if (!confirmDelete) {
        e.preventDefault();
      }
    });
  });
});


