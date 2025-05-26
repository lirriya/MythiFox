window.addEventListener("DOMContentLoaded", function () {
  if (typeof loginError !== "undefined") {
    if (loginError === "username") {
      alert("Username not found. Please check your spelling or sign up first.");
    } else if (loginError === "password") {
      alert("Incorrect password. Try again or reset your password.");
    } else if (loginError === "usertaken") {
      alert("This username is already taken. Please choose another.");
    }
  }

  var wrapper = document.querySelector(".wrapper");
  var loginBtn = document.querySelector(".btnLogin-popup");
  var closeBtn = document.querySelector(".close");
  var arrowBtn = document.getElementById("arrow");
  var dropdown = document.querySelector(".navigation .dropdown");

  if (loginBtn) {
    loginBtn.addEventListener("click", function () {
      wrapper.classList.add("active-popup");
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      wrapper.classList.remove("active-popup");
    });
  }

  if (arrowBtn && dropdown) {
    arrowBtn.addEventListener("click", function () {
      dropdown.classList.toggle("show");
    });
  }

  var editToggle = document.getElementById("toggleEditBtn");
  var editForm = document.getElementById("epf-form");

  if (editToggle && editForm) {
    editToggle.addEventListener("click", function () {
      if (editForm.classList.contains("epf-hidden")) {
        editForm.classList.remove("epf-hidden");
        editToggle.textContent = "Close";
      } else {
        editForm.classList.add("epf-hidden");
        editToggle.textContent = "Edit Profile";
      }
    });
  }

  var allEditButtons = document.querySelectorAll(".edit-btn");

  allEditButtons.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var postId = btn.dataset.postId;
      var contentBox = document.getElementById("content-" + postId);
      var text = contentBox.innerText.trim();

      contentBox.innerHTML = '<textarea name="content" rows="4" style="width:100%;">' + text + '</textarea>';

      var saveButton = document.querySelector('.save-btn[data-post-id="' + postId + '"]');
      if (saveButton) {
        saveButton.style.display = "inline";
      }

      var updateForm = document.getElementById("form-" + postId);
      if (updateForm) {
        updateForm.style.display = "block";
      }
    });
  });

  var allDeleteForms = document.querySelectorAll(".delete-form");

  allDeleteForms.forEach(function (form) {
    form.addEventListener("submit", function (e) {
      var confirmIt = confirm("Are you sure you want to delete this post?");
      if (!confirmIt) {
        e.preventDefault();
      }
    });
  });
});


