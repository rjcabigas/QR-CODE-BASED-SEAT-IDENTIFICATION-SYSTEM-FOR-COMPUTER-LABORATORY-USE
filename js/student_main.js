let currentPC = document.body.dataset.lastPc || "";
let currentComlab = document.body.dataset.lastComlab || "";

let scanner = null;
let scanned = false;

function safeStopScanner() {
    if (scanner) {
        try {
            scanner.stop();
        } catch (e) {}
        scanner = null;
    }
}

document.addEventListener("DOMContentLoaded", () => {

    let isLocked = document.querySelectorAll(".subject-tag").length > 0;

    const btn = document.getElementById("settingBtn");
    const menu = document.getElementById("settingMenu");

    const openBtn = document.getElementById("openScanner");
    const closeBtn = document.getElementById("closeScanner");

    const modal = document.getElementById("scannerModal");
    const pcForm = document.getElementById("pcForm");

    const dropdown = document.getElementById("subjectDropdown");
    const list = document.getElementById("dropdownList");
    const icon = document.getElementById("dropdownIcon");

    const openSubjectBtn = document.getElementById("openSubjectBtn");
    const subjectModal = document.getElementById("subjectModal");

    if (btn && menu) {
        btn.addEventListener("click", e => {
            e.stopPropagation();
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", () => {
            menu.style.display = "none";
        });
    }

    if (openBtn && modal) {
        openBtn.addEventListener("click", () => {
            modal.style.display = "flex";
            safeStopScanner();
            scanned = false;

            scanner = new Html5Qrcode("reader");

            scanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                qr => {
                    if (scanned) return;
                    scanned = true;

                    safeStopScanner();
                    parseQR(qr);

                    const pcInput = document.getElementById("pc_no");
                    const comlabInput = document.getElementById("comlab");

                    if (pcInput) pcInput.value = currentPC;
                    if (comlabInput) comlabInput.value = currentComlab;

                    sendQR(qr);
                }
            ).catch(err => {
                showToast("Camera error", "error");
                console.error(err);
            });
        });
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener("click", () => {
            modal.style.display = "none";
            safeStopScanner();
        });
    }

    if (pcForm) {
        pcForm.addEventListener("submit", e => {
            e.preventDefault();

            fetch("submit_maintenance.php", {
                method: "POST",
                body: new FormData(pcForm)
            })
            .then(r => r.text())
            .then(d => {
                if (d === "success") {
                    showToast("Maintenance reported", "success");
                    closePCModal();
                } else {
                    showToast(d, "error");
                }
            })
            .catch(() => {
                showToast("Network error", "error");
            });
        });
    }

    if (openSubjectBtn && subjectModal) {
        openSubjectBtn.addEventListener("click", () => {
            subjectModal.style.display = "flex";
        });

        subjectModal.addEventListener("click", e => {
            const hasSubject = document.querySelectorAll(".subject-tag").length > 0;

            if (e.target === subjectModal && hasSubject) {
                subjectModal.style.display = "none";
            }
        });
    }

    if (dropdown && list) {
        let tagContainer = document.querySelector(".tag-container");
        tagContainer.classList.add("tag-container");
        dropdown.parentNode.insertBefore(tagContainer, dropdown.nextSibling);

        function toggleSaveBtn() {
            const saveBtn = document.getElementById("saveSubjectsBtn");
            if (!saveBtn) return;
            saveBtn.style.display = tagContainer.children.length > 0 ? "block" : "none";
        }

        function updateNoCurrent() {
            const noCurrent = document.querySelector(".no-current");
            if (!noCurrent) return;

            const items = list.querySelectorAll(".dropdown-item:not(.no-current)");
            noCurrent.style.display = items.length === 0 ? "block" : "none";
        }

        toggleSaveBtn();
        updateNoCurrent();

        dropdown.addEventListener("click", e => {
            e.stopPropagation();

            const isOpen = list.style.display === "block";
            list.style.display = isOpen ? "none" : "block";

            if (icon) {
                if (isOpen) {
                    icon.classList.remove("rotate");
                } else {
                    icon.classList.add("rotate");
                }
            }
        });

        document.addEventListener("click", e => {
            if (!dropdown.contains(e.target) && !list.contains(e.target)) {
                list.style.display = "none";
                if (icon) icon.classList.remove("rotate");
            }
        });

        list.addEventListener("click", e => {
            if (isLocked) return;

            const item = e.target.closest(".dropdown-item");
            if (!item || item.innerText === "No subjects available" || item.classList.contains("no-current")) return;

            const subject = item.dataset.subject;

            if ([...tagContainer.children].some(t => t.dataset.subject === subject)) return;

            const tag = document.createElement("div");
            tag.className = "subject-tag";
            tag.innerText = subject;
            tag.dataset.subject = subject;

            tagContainer.appendChild(tag);
            item.remove();

            const empty = document.querySelector(".no-subject");
            if (empty) empty.style.display = "none";

            toggleSaveBtn();
            updateNoCurrent();
        });

        tagContainer.addEventListener("click", e => {
            if (isLocked) return;

            const tag = e.target.closest(".subject-tag");
            if (!tag) return;

            const subject = tag.dataset.subject;

            tag.remove();

            const newItem = document.createElement("div");
            newItem.className = "dropdown-item";
            newItem.dataset.subject = subject;
            newItem.innerText = subject;

            list.appendChild(newItem);

            fetch("save_subjects.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    delete_subject: subject
                })
            })
            .then(res => res.text())
            .then(data => {
                console.log("Deleted:", data);
            });

            if (tagContainer.children.length === 0) {
                const empty = document.querySelector(".no-subject");
                if (empty) {
                    empty.style.display = "block";
                    empty.innerHTML = `
                        <img src="../img/student_img/no_subject.png" alt="No Subject">
                        <p>No subject has been selected yet.</p>
                    `;
                }
            }

            toggleSaveBtn();
            updateNoCurrent();
        });
    }

    const saveBtn = document.getElementById("saveSubjectsBtn");

    if (saveBtn) {
        saveBtn.addEventListener("click", e => {
            if (isLocked) return;

            e.stopPropagation();

            let subjects = [];

            document.querySelectorAll(".subject-tag").forEach(tag => {
                subjects.push(tag.dataset.subject);
            });

            saveBtn.disabled = true;

            fetch("save_subjects.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ subjects: subjects })
            })
            .then(res => res.text())
            .then(data => {
                if (data === "success") {
                    showToast("Subjects saved", "success");

                    isLocked = true;

                    saveBtn.disabled = true;
                    saveBtn.style.opacity = "0.6";
                    saveBtn.style.cursor = "not-allowed";

                    document.querySelectorAll(".subject-tag").forEach(tag => {
                        tag.style.opacity = "0.6";
                        tag.style.cursor = "not-allowed";
                    });

                    const dropdown = document.getElementById("subjectDropdown");
                    if (dropdown) {
                        dropdown.style.pointerEvents = "none";
                        dropdown.style.opacity = "0.6";
                    }

                    if (subjectModal) {
                        subjectModal.style.display = "none";
                    }
                } else {
                    showToast(data, "error");
                }

                saveBtn.disabled = false;
            })
            .catch(() => {
                showToast("Server error", "error");
                saveBtn.disabled = false;
            });
        });

        saveBtn.addEventListener("touchstart", () => {
            saveBtn.classList.add("pressed");
        });

        saveBtn.addEventListener("touchend", () => {
            saveBtn.classList.remove("pressed");
        });
    }

    if (subjectModal) {
        const hasSubject = document.querySelectorAll(".subject-tag").length > 0;

        if (!hasSubject) {
            subjectModal.style.display = "flex";
        } else {
            isLocked = true;

            document.querySelectorAll(".subject-tag").forEach(tag => {
                tag.style.opacity = "0.6";
                tag.style.cursor = "not-allowed";
            });

            const dropdown = document.getElementById("subjectDropdown");
            if (dropdown) {
                dropdown.style.pointerEvents = "none";
                dropdown.style.opacity = "0.6";
            }
        }
    }

});