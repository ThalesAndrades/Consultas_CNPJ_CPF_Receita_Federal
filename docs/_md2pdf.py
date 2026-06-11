#!/usr/bin/env python3
"""Conversor simples de Markdown -> PDF para o guia de prompts.
Usa fpdf2 + fonte DejaVu (Unicode). Suporta: titulos, paragrafos, listas,
citacoes (blockquote), tabelas, regua horizontal, negrito e codigo inline.
"""
import re
import sys
from fpdf import FPDF

SRC = sys.argv[1]
OUT = sys.argv[2]

FONT_DIR = "/usr/share/fonts/truetype/dejavu"

# Emojis de severidade -> rotulos legiveis (DejaVu nao tem emoji colorido)
EMOJI = {
    "\U0001F7E2": "[BAIXA]",   # verde
    "\U0001F7E1": "[MEDIA]",   # amarelo
    "\U0001F534": "[ALTA]",    # vermelho
}

ACCENT = (15, 76, 129)      # azul para titulos
RULE = (210, 210, 210)
QUOTE_BG = (244, 246, 248)
QUOTE_BAR = (150, 170, 190)
CODE_BG = (238, 240, 242)


def clean(text):
    for k, v in EMOJI.items():
        text = text.replace(k, v)
    return text


def join_continuations(lines):
    """Junta linhas de continuacao (soft-wrap indentado) ao item anterior."""
    out = []
    for ln in lines:
        stripped = ln.strip()
        is_block = (
            not stripped
            or ln.startswith("#")
            or ln.startswith(">")
            or stripped.startswith("|")
            or re.match(r"^---+\s*$", ln)
            or re.match(r"^\s*[-*] ", ln)
            or re.match(r"^\s*\d+\. ", ln)
        )
        # continuacao: indentada, com conteudo, e nao inicia um novo bloco
        if (out and ln[:1] in (" ", "\t") and stripped and not is_block
                and out[-1].strip() and not out[-1].strip().startswith("|")):
            out[-1] = out[-1].rstrip() + " " + stripped
        else:
            out.append(ln)
    return out


class PDF(FPDF):
    def header(self):
        pass

    def footer(self):
        self.set_y(-12)
        self.set_font("DejaVu", "", 8)
        self.set_text_color(150, 150, 150)
        self.cell(0, 8, f"{self.page_no()}", align="C")


pdf = PDF(format="A4")
pdf.set_auto_page_break(auto=True, margin=18)
pdf.add_font("DejaVu", "", f"{FONT_DIR}/DejaVuSans.ttf")
pdf.add_font("DejaVu", "B", f"{FONT_DIR}/DejaVuSans-Bold.ttf")
pdf.add_font("DejaVuMono", "", f"{FONT_DIR}/DejaVuSansMono.ttf")
pdf.add_page()

EPW = pdf.epw  # largura util


def write_inline(text, size=10.5, lh=5.6, indent=0):
    """Escreve um paragrafo com **negrito** e `codigo` inline."""
    text = clean(text)
    if indent:
        pdf.set_x(pdf.l_margin + indent)
    # tokeniza negrito, italico e codigo
    parts = re.split(r"(\*\*.+?\*\*|`.+?`|\*[^*]+?\*)", text)
    pdf.set_text_color(30, 30, 30)
    for part in parts:
        if not part:
            continue
        if part.startswith("**") and part.endswith("**"):
            pdf.set_font("DejaVu", "B", size)
            pdf.write(lh, part[2:-2])
        elif part.startswith("`") and part.endswith("`"):
            pdf.set_font("DejaVuMono", "", size - 0.5)
            pdf.set_text_color(150, 40, 60)
            pdf.write(lh, part[1:-1])
            pdf.set_text_color(30, 30, 30)
        elif part.startswith("*") and part.endswith("*") and len(part) > 2:
            pdf.set_font("DejaVu", "B", size)
            pdf.set_text_color(70, 70, 70)
            pdf.write(lh, part[1:-1])
            pdf.set_text_color(30, 30, 30)
        else:
            pdf.set_font("DejaVu", "", size)
            pdf.write(lh, part)
    pdf.ln(lh + 1.5)


def heading(text, level):
    sizes = {1: 18, 2: 14, 3: 11.5}
    pdf.ln(2 if level > 1 else 1)
    if pdf.get_y() > pdf.h - 40 and level <= 2:
        pdf.add_page()
    pdf.set_font("DejaVu", "B", sizes.get(level, 11))
    pdf.set_text_color(*ACCENT)
    pdf.multi_cell(EPW, sizes.get(level, 11) * 0.5 + 2, clean(text))
    if level == 1:
        pdf.set_draw_color(*ACCENT)
        pdf.set_line_width(0.6)
        y = pdf.get_y() + 1
        pdf.line(pdf.l_margin, y, pdf.l_margin + EPW, y)
        pdf.ln(4)
    else:
        pdf.ln(1.5)
    pdf.set_text_color(30, 30, 30)


def hrule():
    pdf.ln(2)
    pdf.set_draw_color(*RULE)
    pdf.set_line_width(0.3)
    y = pdf.get_y()
    pdf.line(pdf.l_margin, y, pdf.l_margin + EPW, y)
    pdf.ln(4)


def quote(lines):
    text = clean(" ".join(lines))
    x0 = pdf.l_margin
    y0 = pdf.get_y()
    # fundo: desenha um retangulo estimado apos medir
    pdf.set_font("DejaVu", "", 10)
    n = max(1, len(pdf.multi_cell(EPW - 8, 5.6, re.sub(r"[*`]", "", text),
            dry_run=True, output="LINES")))
    h = n * 5.6 + 4
    pdf.set_fill_color(*QUOTE_BG)
    pdf.rect(x0, y0, EPW, h, style="F")
    pdf.set_fill_color(*QUOTE_BAR)
    pdf.rect(x0, y0, 1.6, h, style="F")
    pdf.set_xy(x0 + 5, y0 + 2)
    # render inline (negrito/italico/codigo) dentro da citacao
    parts = re.split(r"(\*\*.+?\*\*|`.+?`|\*[^*]+?\*)", text)
    for part in parts:
        if not part:
            continue
        if part.startswith("**") and part.endswith("**"):
            pdf.set_font("DejaVu", "B", 10)
            pdf.write(5.6, part[2:-2])
        elif part.startswith("`") and part.endswith("`"):
            pdf.set_font("DejaVuMono", "", 9.5)
            pdf.write(5.6, part[1:-1])
        elif part.startswith("*") and part.endswith("*") and len(part) > 2:
            pdf.set_font("DejaVu", "B", 10)
            pdf.write(5.6, part[1:-1])
        else:
            pdf.set_font("DejaVu", "", 10)
            pdf.write(5.6, part)
    pdf.set_xy(x0, y0 + h)
    pdf.ln(3)


def table(rows):
    pdf.ln(1)
    ncol = len(rows[0])
    widths = [EPW / ncol] * ncol
    # cabecalho
    pdf.set_font("DejaVu", "B", 8.5)
    pdf.set_fill_color(*ACCENT)
    pdf.set_text_color(255, 255, 255)
    line_h = 5
    for i, cell in enumerate(rows[0]):
        pdf.multi_cell(widths[i], line_h, clean(cell), border=0, fill=True,
                       new_x="RIGHT", new_y="TOP", max_line_height=line_h, align="L")
    pdf.ln(line_h * 2)
    # corpo
    pdf.set_text_color(30, 30, 30)
    fill = False
    for row in rows[1:]:
        cells = [re.sub(r"[*`]", "", clean(c)) for c in row]
        # mede altura: calcula nº de linhas por celula
        heights = []
        for i, txt in enumerate(cells):
            pdf.set_font("DejaVu", "B" if i == 0 else "", 8.2)
            n = max(1, len(pdf.multi_cell(widths[i], 4.4, txt, dry_run=True,
                    output="LINES", max_line_height=4.4)))
            heights.append(n)
        h = max(heights) * 4.4 + 2
        if pdf.get_y() + h > pdf.h - 18:
            pdf.add_page()
        y0 = pdf.get_y()
        x = pdf.l_margin
        pdf.set_fill_color(247, 249, 251) if fill else pdf.set_fill_color(255, 255, 255)
        for i, txt in enumerate(cells):
            pdf.set_font("DejaVu", "B" if i == 0 else "", 8.2)
            pdf.set_xy(x, y0)
            pdf.multi_cell(widths[i], 4.4, txt, border=0, fill=True,
                           max_line_height=4.4, align="L")
            x += widths[i]
        pdf.set_draw_color(*RULE)
        pdf.set_line_width(0.2)
        pdf.line(pdf.l_margin, y0 + h, pdf.l_margin + EPW, y0 + h)
        pdf.set_xy(pdf.l_margin, y0 + h)
        fill = not fill
    pdf.ln(3)


# ---- parser linha a linha ----
with open(SRC, encoding="utf-8") as f:
    lines = join_continuations(f.read().split("\n"))

i = 0
quote_buf = []
table_buf = []


def flush_quote():
    global quote_buf
    if quote_buf:
        quote(quote_buf)
        quote_buf = []


def flush_table():
    global table_buf
    if table_buf:
        # remove linha separadora |---|
        clean_rows = [r for r in table_buf if not re.match(r"^\s*\|?[\s:|-]+\|?\s*$", r)]
        parsed = []
        for r in clean_rows:
            cells = [c.strip() for c in r.strip().strip("|").split("|")]
            parsed.append(cells)
        if parsed:
            table(parsed)
        table_buf = []


while i < len(lines):
    raw = lines[i]
    line = raw.rstrip()

    if line.strip().startswith("|"):
        flush_quote()
        table_buf.append(line)
        i += 1
        continue
    else:
        flush_table()

    if line.startswith(">"):
        quote_buf.append(line.lstrip(">").strip())
        i += 1
        continue
    else:
        flush_quote()

    if not line.strip():
        i += 1
        continue

    if re.match(r"^---+\s*$", line):
        hrule()
    elif line.startswith("### "):
        heading(line[4:], 3)
    elif line.startswith("## "):
        heading(line[3:], 2)
    elif line.startswith("# "):
        heading(line[2:], 1)
    elif re.match(r"^\s*[-*] ", line):
        indent = (len(line) - len(line.lstrip())) // 2
        content = re.sub(r"^\s*[-*] ", "", line)
        pdf.set_x(pdf.l_margin + 4 + indent * 4)
        pdf.set_font("DejaVu", "", 10.5)
        pdf.set_text_color(*ACCENT)
        pdf.write(5.6, "•  ")
        pdf.set_text_color(30, 30, 30)
        write_inline(content, indent=8 + indent * 4)
    elif re.match(r"^\s*\d+\. ", line):
        num = re.match(r"^\s*(\d+)\. ", line).group(1)
        content = re.sub(r"^\s*\d+\. ", "", line)
        pdf.set_x(pdf.l_margin + 4)
        pdf.set_font("DejaVu", "B", 10.5)
        pdf.set_text_color(*ACCENT)
        pdf.write(5.6, f"{num}.  ")
        pdf.set_text_color(30, 30, 30)
        write_inline(content, indent=10)
    else:
        write_inline(line)
    i += 1

flush_quote()
flush_table()

pdf.output(OUT)
print("OK ->", OUT)
