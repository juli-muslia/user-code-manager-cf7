jQuery(document).ready(function ($) {
  $("#ucm-generate-url-qr-code-button").on("click", function () {
    if (!confirm("Are you sure you want to regenerate all URL & QR codes?")) {
      return;
    }

    // You must pass the form_id dynamically here (replace 123 with actual ID)
    var formId = $(this).data("form-id");

    $.post(
      ucm_ajax.ajax_url,
      {
        action: "ucm_generate_all_qr_codes",
        form_id: formId,
      },
      function (response) {
        if (response.success) {
          alert(response.data.message || "Done!");
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      }
    );
  });
});

// Search function
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("ucm-search");
  const table = document.getElementById("ucm-table");
  const tableRows = table.querySelectorAll("tbody tr");

  searchInput.addEventListener("input", function () {
    const value = this.value.trim().toLowerCase();

    tableRows.forEach((row) => {
      const rowText = row.textContent.toLowerCase();
      row.style.display = rowText.includes(value) ? "" : "none";
    });
  });
});

// Reset Single Code for Single User
jQuery(document).ready(function ($) {
  $(".ucm-reset-single-invitation-code").on("click", function () {
    const postId = $(this).data("id");
    const formId = $(this).data("form-id");

    if (!confirm("Are you sure you want to reset this invitation code?"))
      return;

    $.post(
      ucm_ajax.ajax_url,
      {
        action: "ucm_ajax_reset_single_invitation_code",
        post_id: postId,
        form_id: formId,
      },
      function (response) {
        if (response.success) {
          alert("Invitation code reset successfully!");
          location.reload(); // or update row dynamically
        } else {
          alert(response.message || "An error occurred.");
        }
      }
    );
  });
});

// Reset Codes for all Users
jQuery(document).ready(function ($) {
  $("#ucm-reset-all-button").on("click", function () {
    const formId = $(this).data("form-id");

    if (!confirm("Are you sure you want to reset ALL invitation codes?"))
      return;

    $.post(
      ucm_ajax.ajax_url,
      {
        action: "ucm_ajax_reset_all_invitation_codes",
        form_id: formId,
      },
      function (response) {
        if (response.success) {
          alert("All invitation codes have been reset successfully!");
          location.reload(); // or update UI dynamically
        } else {
          alert(response.message || "An error occurred.");
        }
      }
    );
  });
});

// Prevent Enter key from submitting forms inside the plugin
document
  .querySelectorAll("form.ucm-single-reset-form, #ucm-reset-all-form")
  .forEach((form) => {
    form.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
      }
    });
  });

//Export to CSV
document.addEventListener("DOMContentLoaded", function () {
  const exportBtn = document.getElementById("ucm-export-csv");

  exportBtn.addEventListener("click", function () {
    const nonce = exportBtn.dataset.nonce;
    const formId = exportBtn.dataset.formId;

    const url = new URL(window.location.origin + "/wp-admin/admin-post.php");
    url.searchParams.set("action", "ucm_export_csv");
    url.searchParams.set("ucm_nonce", nonce);
    url.searchParams.set("form_id", formId);

    window.open(url.toString(), "_blank");
  });
});
