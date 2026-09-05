import { app, BrowserWindow, Menu, ipcMain } from 'electron'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const iconPath = path.join(__dirname, '..', 'public', 'favicon.ico')

function loadEnvFile(filePath) {
  if (!fs.existsSync(filePath)) return
  for (const line of fs.readFileSync(filePath, 'utf8').split(/\r?\n/)) {
    const match = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/)
    if (match && process.env[match[1]] === undefined) process.env[match[1]] = match[2].replace(/^['"]|['"]$/g, '')
  }
}

loadEnvFile(path.join(__dirname, '.env'))
const configPath = () => path.join(app.getPath('userData'), 'lumac-settings.json')
const config = {
  appUrl: process.env.LUMAC_APP_URL || '',
  posPrinter: process.env.LUMAC_POS_PRINTER || '',
  kotPrinter: process.env.LUMAC_KOT_PRINTER || '',
  barcodePrinter: process.env.LUMAC_BARCODE_PRINTER || '',
}

function loadConfig() {
  try { Object.assign(config, JSON.parse(fs.readFileSync(configPath(), 'utf8'))) } catch { /* first run */ }
}

function saveConfig(values) {
  Object.assign(config, {
    appUrl: String(values.appUrl || '').trim(),
    posPrinter: String(values.posPrinter || ''),
    kotPrinter: String(values.kotPrinter || ''),
    barcodePrinter: String(values.barcodePrinter || ''),
  })
  fs.writeFileSync(configPath(), JSON.stringify(config, null, 2))
}

let mainWindow
let settingsWindow

function splashHtml() {
  return `<!doctype html><html><head><meta charset="utf-8"><style>
    *{box-sizing:border-box}body{margin:0;height:100vh;display:grid;place-items:center;background:#c96b4b;color:#fff;font:600 22px system-ui,sans-serif}
    .wrap{text-align:center}.mark{font-size:48px;font-weight:800;letter-spacing:5px;text-shadow:0 2px 8px rgba(62,35,27,.2)}.lu{color:#fff}.mac{color:#123b63}.loading{margin-top:16px;color:#fff5ed;font-size:12px;letter-spacing:2px;text-transform:uppercase}.contact{margin-top:14px;color:#fff;font-size:11px;line-height:1.7;letter-spacing:.5px}.contact strong{color:#123b63}
    .dot{display:inline-block;width:7px;height:7px;margin:0 3px;border-radius:50%;background:#123b63;animation:p 1s infinite}.dot:nth-child(2){animation-delay:.15s}.dot:nth-child(3){animation-delay:.3s}@keyframes p{50%{opacity:.25}}
  </style></head><body><div class="wrap"><div class="mark"><span class="lu">LU</span><span class="mac">MAC</span></div><div class="loading">Loading<span class="dot"></span><span class="dot"></span><span class="dot"></span></div><div class="contact"><div>+94 76 464 3050</div><div><strong>lumac.lk</strong></div></div></div></body></html>`
}

function createWindow() {
  const splash = new BrowserWindow({ width: 420, height: 260, frame: false, resizable: false, show: true, icon: iconPath })
  splash.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(splashHtml())}`)
  mainWindow = new BrowserWindow({
    width: 1440,
    height: 900,
    show: false,
    icon: iconPath,
    webPreferences: { preload: path.join(__dirname, 'preload.cjs'), contextIsolation: true, nodeIntegration: false },
  })

  mainWindow.loadURL(config.appUrl)
  mainWindow.webContents.once('did-finish-load', () => {
    splash.destroy()
    mainWindow.show()
  })
  mainWindow.on('closed', () => { mainWindow = null })
}

function settingsHtml() {
  return `<!doctype html><html><head><meta charset="utf-8"><style>
    *{box-sizing:border-box}body{margin:0;padding:28px;background:#f9fafb;color:#1f2937;font:14px system-ui,sans-serif}h1{margin:0 0 6px;color:#b45309;font-size:24px}p{margin:0 0 22px;color:#6b7280}label{display:block;margin:14px 0 6px;font-weight:600}input,select{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font:inherit}button{margin-top:24px;padding:11px 18px;border:0;border-radius:8px;background:#d97706;color:#fff;font-weight:700;cursor:pointer}button:disabled{opacity:.6}.status{margin-left:12px;color:#b45309}
  </style></head><body><h1>LUMAC Settings</h1><p>Choose the live app and printer for this computer.</p>
  <label>Live app URL</label><input id="appUrl" type="url" placeholder="https://your-live-app.example.com">
  <label>POS / receipt printer</label><select id="posPrinter"></select>
  <label>KOT / kitchen printer</label><select id="kotPrinter"></select>
  <label>Barcode printer</label><select id="barcodePrinter"></select>
  <button id="save">Save and open LUMAC</button><span id="status" class="status"></span>
  <script>
    const fields = ['posPrinter','kotPrinter','barcodePrinter']
    const config = ${JSON.stringify(config)}
    document.querySelector('#appUrl').value = config.appUrl
    window.electronAPI.getPrinters().then(printers => fields.forEach(id => {
      const select = document.querySelector('#' + id)
      select.innerHTML = '<option value="">System default</option>' + printers.map(p => '<option></option>').join('')
      printers.forEach((printer, index) => { select.options[index + 1].text = printer; select.options[index + 1].value = printer })
      select.value = config[id]
    }))
    document.querySelector('#save').onclick = async () => {
      const values = { appUrl: document.querySelector('#appUrl').value, ...Object.fromEntries(fields.map(id => [id, document.querySelector('#' + id).value])) }
      if (!values.appUrl) { document.querySelector('#status').textContent = 'Enter an app URL'; return }
      document.querySelector('#save').disabled = true
      await window.electronAPI.saveConfig(values)
    }
  </script></body></html>`
}

async function openSettings() {
  if (settingsWindow && !settingsWindow.isDestroyed()) { settingsWindow.focus(); return }
  settingsWindow = new BrowserWindow({ width: 520, height: 620, resizable: false, icon: iconPath, parent: mainWindow || undefined,
    webPreferences: { preload: path.join(__dirname, 'preload.cjs'), contextIsolation: true, nodeIntegration: false } })
  await settingsWindow.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(settingsHtml())}`)
  settingsWindow.on('closed', () => { settingsWindow = null })
}

async function printCurrent({ type = 'pos' } = {}) {
  if (!mainWindow) throw new Error('Main window is not ready')
  const preferredPrinter = { pos: config.posPrinter, kot: config.kotPrinter }[type] || config.posPrinter
  const printer = await resolvePrinter(preferredPrinter, mainWindow)
  const printOptions = { silent: true, printBackground: true }
  if (printer) printOptions.deviceName = printer
  return new Promise((resolve, reject) => {
    mainWindow.webContents.print(printOptions, (success, failureReason) => {
      if (!success) reject(new Error(failureReason || 'Silent print failed'))
      else resolve(true)
    })
  })
}

async function resolvePrinter(preferredName, browserWindow) {
  if (!preferredName) return undefined
  const printers = await browserWindow.webContents.getPrintersAsync()
  const preferred = String(preferredName).trim().toLowerCase()
  return printers.find(printer => [printer.name, printer.displayName].some(name => String(name || '').trim().toLowerCase() === preferred))?.name
}

async function printBarcode(html) {
  const printWindow = new BrowserWindow({ show: false, webPreferences: { sandbox: true } })
  try {
    await printWindow.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(html)}`)
    const printer = await resolvePrinter(config.barcodePrinter || config.posPrinter, printWindow)
    const printOptions = { silent: true, printBackground: true }
    if (printer) printOptions.deviceName = printer
    return await new Promise((resolve, reject) => {
      printWindow.webContents.print(printOptions, (success, failureReason) => {
        if (!success) reject(new Error(failureReason || 'Barcode print failed'))
        else resolve(true)
      })
    })
  } finally {
    if (!printWindow.isDestroyed()) printWindow.destroy()
  }
}

app.whenReady().then(() => {
  app.setAppUserModelId('com.lumac.pos')
  loadConfig()
  ipcMain.handle('print-current', (_event, options) => printCurrent(options))
  ipcMain.handle('print-barcode', (_event, html) => printBarcode(html))
  ipcMain.handle('get-config', () => config)
  ipcMain.handle('get-printers', async (event) => (await BrowserWindow.fromWebContents(event.sender).webContents.getPrintersAsync()).map(printer => printer.name))
  ipcMain.handle('save-config', async (_event, values) => {
    saveConfig(values)
    if (mainWindow && !mainWindow.isDestroyed()) await mainWindow.loadURL(config.appUrl)
    else createWindow()
    if (settingsWindow && !settingsWindow.isDestroyed()) settingsWindow.close()
    return config
  })
  Menu.setApplicationMenu(Menu.buildFromTemplate([{ label: 'LUMAC', submenu: [{ label: 'Settings', click: openSettings }, { type: 'separator' }, { role: 'quit' }] }]))
  if (config.appUrl) createWindow()
  else openSettings()
  app.on('activate', () => { if (BrowserWindow.getAllWindows().length === 0) createWindow() })
})

app.on('window-all-closed', () => { if (process.platform !== 'darwin') app.quit() })