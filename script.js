"use strict";
jQuery(document).ready(function ($) {
  if ($("#post-11620").length) {
    $(".wp-block-button.delete-account").click(function (e) {
      e.preventDefault();
      let confirm = window.confirm(
        "Vous êtes sur le point de supprimer votre compte. Cette action ne peut pas être annulée. Êtes-vous certain de vouloir continuer ?"
      );

      if (confirm) {
        // ajax call to delete account
        $.ajax({
          url: "/wp-content/themes/twentytwenty-child/ajax-delete-account.php",
          type: "POST",
          data: {
            confirm: true,
          },
          success: function (result) {
            result = JSON.parse(result);
            if (result.success) {
              window.location.href = "/";
            } else {
              alert(
                "Une erreur est survenue. Veuillez réessayer ultérieurement."
              );
            }
          },
        });
      }
    });
  }

  // If element with id 'gform_wrapper_3' exists, run the following code
  if ($("#gform_wrapper_3").length) {
    $("input[name=input_57]").on("blur", function () {
      // Disable input 58
      $("input[name=input_58]").attr("disabled", "disabled");
      $("input[name=input_105]").attr("disabled", "disabled");
      // Get the value of the input
      var inputValue = $(this).val();

      // If the input value is empty do nothing
      if (inputValue == "") {
        $("input[name=input_58]").val("");
        $("input[name=input_58]").removeAttr("disabled");
        $("input[name=input_105]").val("");
        $("input[name=input_105]").removeAttr("disabled");
        return;
      }

      $("input[name=input_58]").val("...");
      $("input[name=input_105]").val("...");
      // Fetch city and district
      {
        const getLocality = (res) => {
          const locality = res[0].address_components.find((c) =>
            c.types.includes("locality")
          )?.short_name;
          const sublocality = res[0].address_components.find((c) =>
            c.types.includes("sublocality")
          )?.short_name;
          const neighborhood = res[0].address_components.find((c) =>
            c.types.includes("neighborhood")
          )?.short_name;
          return { locality, sublocality, neighborhood };
        };

        const url = new URL(
          "https://maps.googleapis.com/maps/api/geocode/json"
        );
        url.searchParams.set("language", "fr");
        url.searchParams.set("address", inputValue);
        url.searchParams.set("key", "AIzaSyA4FMUXGEaTBXDiZkLlgsihCKPCsi28CtA");

        // fetch the data from the API
        fetch(url)
          .then((response) => response.json())
          .then(({ status, results }) => {
            if (status != "OK") return;

            const { locality, sublocality, neighborhood } =
              getLocality(results);
            if (locality) {
              $("input[name=input_58]").val(locality);
            }
            if (sublocality || neighborhood) {
              $("input[name=input_105]").val(sublocality ?? neighborhood);
            } else {
              $("input[name=input_105]").val(locality);
            }

            $("input[name=input_58]").removeAttr("disabled");
            $("input[name=input_105]").removeAttr("disabled");
          });
      }
    });

    $("select[multiple].gfield_select").each(function () {
      $(this).select2({
        placeholder: "Veuillez choisir une option",
        width: "100%",
        maximumSelectionLength: 3,
        language: {
          maximumSelected: function (e) {
            return "Vous ne pouvez sélectionner que " + e.maximum + " éléments";
          },
        },
        dropdownParent: $(this).parent(),
      });
    });
  }

  if (
    ($("#gform_confirmation_wrapper_3").length ||
      $(".form_saved_message").length) &&
    $("#post-11627").length
  ) {
    $(".wp-block-group.titre-section").css("display", "none");
  }

  // If the search form exists, run the following code
  if ($(".gv-widget-search[data-viewid=11618]").length) {
    $(".gv-search-field-multiselect select[multiple]").each(function () {
      // Unselect the selected option
      // $(this).find("option:selected").prop("selected", false);
      $(this).select2({
        placeholder: "Veuillez choisir une option",
        width: "100%",
        maximumSelectionLength: 3,
        language: {
          maximumSelected: function (e) {
            return "Vous ne pouvez sélectionner que " + e.maximum + " éléments";
          },
        },
        dropdownParent: $(this).parent(),
      });
    });
  }

  // If on single page, make backlink go back in history
  if ($(".gv-list-single-container.gv-container-11618").length) {
    document.querySelector(".gv-back-link > a").onclick = (e) => {
      e.preventDefault();
      history.back();
    };
  }
});
