import setuptools

with open("README.md", "r") as fh:
    long_description = fh.read()

setuptools.setup(
    name="nashlink",
    version="1.0.0",
    author="Nash",
    author_email="contact@nash.io",
    description="",
    long_description=long_description,
    long_description_content_type="text/markdown",
    url="https://github.com/nash-io/nashlink-plugins-and-sdks/sdk-python/",
    packages=setuptools.find_packages(),
    classifiers=[
        "Programming Language :: Python :: 2",
        "Programming Language :: Python :: 3",
        "License :: OSI Approved :: MIT License",
        "Operating System :: OS Independent",
    ],
    python_requires='>=2.6',
)
