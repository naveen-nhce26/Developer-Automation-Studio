document.addEventListener("DOMContentLoaded", function () {

    // Get HTML elements
    const form = document.querySelector("form");
    const fileInput = document.getElementById("log_file");
    const textArea = document.getElementById("log_content");

    const dropArea = document.getElementById("drop_area");
    const fileNameDisplay = document.getElementById("file_name");

    const analyzeButton = document.getElementById("analyze_button");
    const clearButton = document.getElementById("clear_button");


    // =====================================
    // 1. DISPLAY SELECTED FILE NAME
    // =====================================

    fileInput.addEventListener("change", function () {

        if (this.files.length > 0) {

            const file = this.files[0];

            fileNameDisplay.textContent = "Selected: " + file.name;

            textArea.value = "";

        } else {

            fileNameDisplay.textContent = "No file selected";

        }

    });


    // =====================================
    // 2. DRAG AND DROP FUNCTIONALITY
    // =====================================

    dropArea.addEventListener("dragover", function (event) {

        event.preventDefault();

        dropArea.classList.add("drag-active");

    });


    dropArea.addEventListener("dragleave", function () {

        dropArea.classList.remove("drag-active");

    });


    dropArea.addEventListener("drop", function (event) {

        event.preventDefault();

        dropArea.classList.remove("drag-active");

        const files = event.dataTransfer.files;

        if (files.length > 0) {

            const file = files[0];

            const extension = file.name.split(".").pop().toLowerCase();

            if (extension !== "log" && extension !== "txt") {

                alert("Please select a .log or .txt file.");

                return;

            }

            fileInput.files = files;

            fileNameDisplay.textContent = "Selected: " + file.name;

            textArea.value = "";

        }

    });


    // =====================================
    // 3. CLEAR INPUTS
    // =====================================

    clearButton.addEventListener("click", function () {

        fileInput.value = "";

        textArea.value = "";

        fileNameDisplay.textContent = "No file selected";

        analyzeButton.disabled = false;

        analyzeButton.textContent = "Analyze Logs";

    });


    // =====================================
    // 4. FORM VALIDATION
    // =====================================

    form.addEventListener("submit", function (event) {

        const fileSelected = fileInput.files.length > 0;

        const contentEntered = textArea.value.trim() !== "";


        if (!fileSelected && !contentEntered) {

            event.preventDefault();

            alert("Please upload a log file or paste log content.");

            return;

        }


        if (fileSelected && contentEntered) {

            event.preventDefault();

            alert("Please use either file upload or pasted content, not both.");

            return;

        }


        // Loading feedback
        analyzeButton.textContent = "Analyzing...";

        analyzeButton.disabled = true;

    });

});