const api = {
    async getTitles() {
        const response = await fetch('/api/titles');
        if (!response.ok) {
            throw new Error(`Could not load titles (status ${response.status}).`);
        }

        return response.json();
    },
};

const typeLabels = { film: 'Film', series: 'Series' };

const titleList = document.getElementById('title-list');
const errorMessage = document.getElementById('error-message');

function renderTitles(titles) {
    titleList.replaceChildren();

    if (titles.length === 0) {
        const cell = titleList.insertRow().insertCell();
        cell.colSpan = 4;
        cell.textContent = 'No titles yet.';
        return;
    }

    for (const title of titles) {
        const row = titleList.insertRow();
        row.insertCell().textContent = title.name;
        row.insertCell().textContent = typeLabels[title.type];
        row.insertCell().textContent = title.year;
        row.insertCell().textContent = title.watched ? 'Yes' : 'No';
    }
}

function showError(message) {
    errorMessage.textContent = message;
    errorMessage.hidden = false;
}

async function loadTitles() {
    try {
        renderTitles(await api.getTitles());
    } catch (error) {
        showError(error.message);
    }
}

loadTitles();
