const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('electronAPI', {
  printReceipt: () => ipcRenderer.invoke('print-current', { type: 'pos' }),
  printKot: () => ipcRenderer.invoke('print-current', { type: 'kot' }),
  printBarcode: (html) => ipcRenderer.invoke('print-barcode', html),
  getConfig: () => ipcRenderer.invoke('get-config'),
  getPrinters: () => ipcRenderer.invoke('get-printers'),
  saveConfig: (values) => ipcRenderer.invoke('save-config', values),
})